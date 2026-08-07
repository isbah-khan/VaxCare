<?php
declare(strict_types=1);

/** Resolves the hospital profile owned by the signed-in hospital account. */
function hospital_profile(PDO $pdo, int $userId): array
{
    $statement = $pdo->prepare('SELECT h.*, u.name, u.email, u.phone FROM hospitals h JOIN users u ON u.id = h.user_id WHERE h.user_id = ?');
    $statement->execute([$userId]);
    $hospital = $statement->fetch();
    if (!$hospital) {
        http_response_code(404);
        exit('Hospital profile not found.');
    }
    return $hospital;
}

/** Shows approved appointment workload and vaccine availability totals. */
function hospital_dashboard(PDO $pdo): never
{
    $user = require_role('hospital');
    $hospital = hospital_profile($pdo, (int) $user['id']);
    $statsStatement = $pdo->prepare(
        'SELECT
            (SELECT COUNT(*) FROM appointments WHERE hospital_id = ? AND status = "approved") approved_count,
            (SELECT COUNT(*) FROM appointments WHERE hospital_id = ? AND status = "approved" AND appointment_date = CURDATE()) today_count,
            (SELECT COUNT(*) FROM hospital_vaccines WHERE hospital_id = ? AND is_available = 1 AND stock_quantity > 0) available_count,
            (SELECT COUNT(*) FROM vaccination_records WHERE hospital_id = ?) completed_count'
    );
    $statsStatement->execute(array_fill(0, 4, $hospital['id']));
    $appointmentsStatement = $pdo->prepare(
        'SELECT a.*, c.name child_name, c.date_of_birth, c.medical_notes, p.name parent_name, p.phone parent_phone,
                v.name vaccine_name, v.dose_label
         FROM appointments a JOIN children c ON c.id = a.child_id JOIN users p ON p.id = a.parent_id
         JOIN vaccines v ON v.id = a.vaccine_id
         WHERE a.hospital_id = ? AND a.status = "approved"
         ORDER BY a.appointment_date, a.appointment_time LIMIT 8'
    );
    $appointmentsStatement->execute([$hospital['id']]);
    render('hospital/dashboard', [
        'title' => 'Hospital dashboard', 'hospital' => $hospital,
        'stats' => $statsStatement->fetch(), 'appointments' => $appointmentsStatement->fetchAll(),
    ]);
}

/** Displays the hospital's available/unavailable status for every vaccine. */
function hospital_inventory(PDO $pdo): never
{
    $user = require_role('hospital');
    $hospital = hospital_profile($pdo, (int) $user['id']);
    $statement = $pdo->prepare(
        'SELECT v.*, COALESCE(hv.is_available, 0) is_available, COALESCE(hv.stock_quantity, 0) stock_quantity, hv.updated_at
         FROM vaccines v LEFT JOIN hospital_vaccines hv ON hv.vaccine_id = v.id AND hv.hospital_id = ?
         WHERE v.is_active = 1 ORDER BY v.recommended_age_months, v.name, v.dose_label'
    );
    $statement->execute([$hospital['id']]);
    render('hospital/inventory', ['title' => 'Vaccine status', 'hospital' => $hospital, 'vaccines' => $statement->fetchAll()]);
}

/** Saves availability and non-negative stock values for the hospital's vaccine list. */
function hospital_inventory_update(PDO $pdo): never
{
    $user = require_role('hospital');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
    verify_csrf();
    $hospital = hospital_profile($pdo, (int) $user['id']);
    $available = is_array($_POST['available'] ?? null) ? $_POST['available'] : [];
    $stocks = is_array($_POST['stock'] ?? null) ? $_POST['stock'] : [];
    $vaccineIds = $pdo->query('SELECT id FROM vaccines WHERE is_active = 1')->fetchAll(PDO::FETCH_COLUMN);
    $statement = $pdo->prepare(
        'INSERT INTO hospital_vaccines (hospital_id, vaccine_id, is_available, stock_quantity) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE is_available = VALUES(is_available), stock_quantity = VALUES(stock_quantity)'
    );
    $pdo->beginTransaction();
    try {
        foreach ($vaccineIds as $vaccineId) {
            $stock = max(0, min(100000, (int) ($stocks[$vaccineId] ?? 0)));
            $isAvailable = isset($available[$vaccineId]) && $stock > 0 ? 1 : 0;
            $statement->execute([$hospital['id'], $vaccineId, $isAvailable, $stock]);
        }
        $pdo->commit();
        flash('success', 'Vaccine availability updated for parents and the administrator.');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', 'Vaccine status could not be updated.');
    }
    redirect('hospital.inventory');
}

/** Lists appointments after admin approval, plus completed outcomes. */
function hospital_appointments(PDO $pdo): never
{
    $user = require_role('hospital');
    $hospital = hospital_profile($pdo, (int) $user['id']);
    $status = (string) ($_GET['status'] ?? 'approved');
    if (!in_array($status, ['all', 'approved', 'completed', 'not_vaccinated'], true)) $status = 'approved';
    $sql = 'SELECT a.*, c.name child_name, c.date_of_birth, c.gender, c.blood_group, c.medical_notes,
                   p.name parent_name, p.phone parent_phone, v.name vaccine_name, v.dose_label,
                   vr.batch_number, vr.administered_at
            FROM appointments a JOIN children c ON c.id = a.child_id JOIN users p ON p.id = a.parent_id
            JOIN vaccines v ON v.id = a.vaccine_id LEFT JOIN vaccination_records vr ON vr.appointment_id = a.id
            WHERE a.hospital_id = ?';
    $parameters = [$hospital['id']];
    if ($status !== 'all') { $sql .= ' AND a.status = ?'; $parameters[] = $status; }
    else $sql .= ' AND a.status IN ("approved", "completed", "not_vaccinated")';
    $sql .= ' ORDER BY FIELD(a.status, "approved", "completed", "not_vaccinated"), a.appointment_date DESC, a.appointment_time';
    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);
    render('hospital/appointments', [
        'title' => 'Hospital appointments', 'hospital' => $hospital,
        'appointments' => $statement->fetchAll(), 'status' => $status,
    ]);
}

/** Records vaccinated/not-vaccinated outcomes and creates the formal report record. */
function hospital_appointment_update(PDO $pdo): never
{
    $user = require_role('hospital');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
    verify_csrf();
    $hospital = hospital_profile($pdo, (int) $user['id']);
    $appointmentId = (int) input('appointment_id');
    $outcome = input('outcome');
    $batchNumber = input('batch_number');
    $notes = input('hospital_notes');
    if (!in_array($outcome, ['completed', 'not_vaccinated'], true)) {
        flash('error', 'Choose Vaccinated or Not vaccinated.');
        redirect('hospital.appointments');
    }
    if ($outcome === 'completed' && $batchNumber === '') {
        flash('error', 'Enter the vaccine batch number for a completed vaccination.');
        redirect('hospital.appointments');
    }

    try {
        $pdo->beginTransaction();
        $lookup = $pdo->prepare(
            'SELECT * FROM appointments WHERE id = ? AND hospital_id = ? AND status = "approved" FOR UPDATE'
        );
        $lookup->execute([$appointmentId, $hospital['id']]);
        $appointment = $lookup->fetch();
        if (!$appointment) throw new RuntimeException('The appointment is not approved or was already updated.');
        if ($outcome === 'completed' && $appointment['appointment_date'] > date('Y-m-d')) {
            throw new RuntimeException('A vaccination cannot be recorded before the approved appointment date.');
        }

        if ($outcome === 'completed') {
            $stock = $pdo->prepare(
                'SELECT stock_quantity, is_available FROM hospital_vaccines
                 WHERE hospital_id = ? AND vaccine_id = ? FOR UPDATE'
            );
            $stock->execute([$hospital['id'], $appointment['vaccine_id']]);
            $inventory = $stock->fetch();
            if (!$inventory || !(int) $inventory['is_available'] || (int) $inventory['stock_quantity'] < 1) {
                throw new RuntimeException('This vaccine is currently unavailable. Update vaccine status before recording it.');
            }
            $administeredAt = date('Y-m-d H:i:s');
            $pdo->prepare(
                'INSERT INTO vaccination_records
                 (appointment_id, child_id, vaccine_id, hospital_id, administered_at, batch_number, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $appointment['id'], $appointment['child_id'], $appointment['vaccine_id'], $hospital['id'],
                $administeredAt, $batchNumber, $notes ?: null,
            ]);
            $pdo->prepare(
                'UPDATE hospital_vaccines
                 SET stock_quantity = stock_quantity - 1,
                     is_available = IF(stock_quantity - 1 > 0, is_available, 0)
                 WHERE hospital_id = ? AND vaccine_id = ?'
            )->execute([$hospital['id'], $appointment['vaccine_id']]);
            $pdo->prepare(
                'UPDATE appointments SET status = "completed", hospital_notes = ?, completed_at = ? WHERE id = ?'
            )->execute([$notes ?: null, $administeredAt, $appointment['id']]);
        } else {
            $pdo->prepare(
                'UPDATE appointments SET status = "not_vaccinated", hospital_notes = ?, completed_at = NOW() WHERE id = ?'
            )->execute([$notes ?: 'Vaccination was not administered.', $appointment['id']]);
        }
        $pdo->commit();
        flash('success', $outcome === 'completed' ? 'Vaccination recorded and added to the parent report.' : 'Appointment marked as not vaccinated.');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', $exception->getMessage());
    }
    redirect('hospital.appointments');
}
