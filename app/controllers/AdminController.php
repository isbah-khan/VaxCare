<?php
declare(strict_types=1);

/** Loads counts and upcoming vaccination activity for the admin overview. */
function admin_dashboard(PDO $pdo): never
{
    require_role('admin');
    $stats = [
        'children' => (int) $pdo->query('SELECT COUNT(*) FROM children')->fetchColumn(),
        'pending' => (int) $pdo->query('SELECT COUNT(*) FROM appointments WHERE status = "pending"')->fetchColumn(),
        'hospitals' => (int) $pdo->query('SELECT COUNT(*) FROM hospitals h JOIN users u ON u.id = h.user_id WHERE u.status = "active"')->fetchColumn(),
        'completed' => (int) $pdo->query('SELECT COUNT(*) FROM vaccination_records')->fetchColumn(),
    ];
    $upcoming = $pdo->query(
        'SELECT a.id, a.appointment_date, a.appointment_time, a.status, c.name child_name,
                v.name vaccine_name, v.dose_label, u.name hospital_name
         FROM appointments a
         JOIN children c ON c.id = a.child_id
         JOIN vaccines v ON v.id = a.vaccine_id
         JOIN hospitals h ON h.id = a.hospital_id
         JOIN users u ON u.id = h.user_id
         WHERE a.appointment_date >= CURDATE() AND a.status IN ("pending", "approved")
         ORDER BY a.appointment_date, a.appointment_time LIMIT 8'
    )->fetchAll();
    render('admin/dashboard', ['title' => 'Admin overview', 'stats' => $stats, 'upcoming' => $upcoming]);
}

/** Lists every child profile with parent contact details. */
function admin_children(PDO $pdo): never
{
    require_role('admin');
    $search = trim((string) ($_GET['search'] ?? ''));
    $sql = 'SELECT c.*, u.name parent_name, u.email parent_email, u.phone parent_phone,
                   (SELECT COUNT(*) FROM vaccination_records vr WHERE vr.child_id = c.id) completed_doses
            FROM children c JOIN users u ON u.id = c.parent_id';
    $parameters = [];
    if ($search !== '') {
        $sql .= ' WHERE c.name LIKE ? OR u.name LIKE ? OR u.email LIKE ?';
        $term = '%' . $search . '%';
        $parameters = [$term, $term, $term];
    }
    $sql .= ' ORDER BY c.name';
    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);
    render('admin/children', ['title' => 'All child details', 'children' => $statement->fetchAll(), 'search' => $search]);
}

/** Lists parent appointment requests for approval or rejection. */
function admin_requests(PDO $pdo): never
{
    require_role('admin');
    $status = (string) ($_GET['status'] ?? 'pending');
    if (!in_array($status, ['all', 'pending', 'approved', 'rejected'], true)) $status = 'pending';
    $sql = 'SELECT a.*, c.name child_name, p.name parent_name, p.email parent_email,
                   hu.name hospital_name, v.name vaccine_name, v.dose_label
            FROM appointments a
            JOIN children c ON c.id = a.child_id
            JOIN users p ON p.id = a.parent_id
            JOIN hospitals h ON h.id = a.hospital_id
            JOIN users hu ON hu.id = h.user_id
            JOIN vaccines v ON v.id = a.vaccine_id';
    $parameters = [];
    if ($status !== 'all') {
        $sql .= ' WHERE a.status = ?';
        $parameters[] = $status;
    } else {
        $sql .= ' WHERE a.status IN ("pending", "approved", "rejected")';
    }
    $sql .= ' ORDER BY FIELD(a.status, "pending", "approved", "rejected"), a.appointment_date, a.created_at';
    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);
    render('admin/requests', ['title' => 'Parent requests', 'requests' => $statement->fetchAll(), 'status' => $status]);
}

/** Applies the admin's decision to a still-pending parent request. */
function admin_request_update(PDO $pdo): never
{
    $admin = require_role('admin');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
    verify_csrf();
    $appointmentId = (int) input('appointment_id');
    $decision = input('decision');
    $notes = input('admin_notes');
    if (!in_array($decision, ['approved', 'rejected'], true)) {
        flash('error', 'Choose approve or reject.');
        redirect('admin.requests');
    }
    $statement = $pdo->prepare(
        'UPDATE appointments
         SET status = ?, admin_notes = ?, approved_by = ?, approved_at = NOW()
         WHERE id = ? AND status = "pending"'
    );
    $statement->execute([$decision, $notes ?: null, $admin['id'], $appointmentId]);
    flash($statement->rowCount() ? 'success' : 'warning', $statement->rowCount()
        ? 'The appointment request was ' . $decision . '.'
        : 'That request was already processed.');
    redirect('admin.requests');
}

/** Shows the required hospital list and the add/update form. */
function admin_hospitals(PDO $pdo): never
{
    require_role('admin');
    $hospitals = $pdo->query(
        'SELECT h.*, u.name, u.email, u.phone, u.status,
                (SELECT COUNT(*) FROM appointments a WHERE a.hospital_id = h.id) booking_count,
                (SELECT COUNT(*) FROM hospital_vaccines hv WHERE hv.hospital_id = h.id AND hv.is_available = 1) available_count
         FROM hospitals h JOIN users u ON u.id = h.user_id ORDER BY u.status DESC, u.name'
    )->fetchAll();
    $editing = null;
    if (!empty($_GET['edit'])) {
        $statement = $pdo->prepare('SELECT h.*, u.name, u.email, u.phone, u.status FROM hospitals h JOIN users u ON u.id = h.user_id WHERE h.id = ?');
        $statement->execute([(int) $_GET['edit']]);
        $editing = $statement->fetch() ?: null;
    }
    render('admin/hospitals', ['title' => 'Hospital management', 'hospitals' => $hospitals, 'editing' => $editing]);
}

/** Creates or updates a hospital and its login details transactionally. */
function admin_hospital_save(PDO $pdo): never
{
    require_role('admin');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
    verify_csrf();
    $hospitalId = (int) input('hospital_id');
    $name = input('name');
    $email = strtolower(input('email'));
    $phone = input('phone');
    $address = input('address');
    $city = input('city');
    $location = input('location_details');
    $password = input('password');
    $status = input('status', 'active');

    if (mb_strlen($name) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($address) < 5 || mb_strlen($city) < 2) {
        flash('error', 'Complete the hospital name, valid email, address, and city.');
        redirect('admin.hospitals', $hospitalId ? ['edit' => $hospitalId] : []);
    }
    if (!$hospitalId && strlen($password) < 8) {
        flash('error', 'A new hospital password must contain at least 8 characters.');
        redirect('admin.hospitals');
    }
    if (!in_array($status, ['active', 'inactive'], true)) $status = 'active';

    try {
        $pdo->beginTransaction();
        if ($hospitalId) {
            $lookup = $pdo->prepare('SELECT user_id FROM hospitals WHERE id = ?');
            $lookup->execute([$hospitalId]);
            $userId = (int) $lookup->fetchColumn();
            if (!$userId) throw new RuntimeException('Hospital not found.');
            $sql = 'UPDATE users SET name = ?, email = ?, phone = ?, status = ?';
            $parameters = [$name, $email, $phone ?: null, $status];
            if ($password !== '') {
                if (strlen($password) < 8) throw new RuntimeException('Password must contain at least 8 characters.');
                $sql .= ', password_hash = ?';
                $parameters[] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = ?';
            $parameters[] = $userId;
            $pdo->prepare($sql)->execute($parameters);
            $pdo->prepare('UPDATE hospitals SET address = ?, city = ?, location_details = ? WHERE id = ?')
                ->execute([$address, $city, $location ?: null, $hospitalId]);
        } else {
            $pdo->prepare('INSERT INTO users (role, name, email, password_hash, phone) VALUES ("hospital", ?, ?, ?, ?)')
                ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone ?: null]);
            $userId = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO hospitals (user_id, address, city, location_details) VALUES (?, ?, ?, ?)')
                ->execute([$userId, $address, $city, $location ?: null]);
            $newHospitalId = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO hospital_vaccines (hospital_id, vaccine_id) SELECT ?, id FROM vaccines WHERE is_active = 1')
                ->execute([$newHospitalId]);
        }
        $pdo->commit();
        flash('success', $hospitalId ? 'Hospital details updated.' : 'Hospital added with a login account.');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = $exception instanceof PDOException && $exception->getCode() === '23000'
            ? 'That email address is already in use.' : $exception->getMessage();
        flash('error', $message ?: 'Hospital could not be saved.');
    }
    redirect('admin.hospitals');
}

/** Deletes unused hospitals; those with history are safely deactivated. */
function admin_hospital_delete(PDO $pdo): never
{
    require_role('admin');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
    verify_csrf();
    $hospitalId = (int) input('hospital_id');
    $lookup = $pdo->prepare(
        'SELECT h.user_id, (SELECT COUNT(*) FROM appointments a WHERE a.hospital_id = h.id) booking_count
         FROM hospitals h WHERE h.id = ?'
    );
    $lookup->execute([$hospitalId]);
    $hospital = $lookup->fetch();
    if (!$hospital) {
        flash('warning', 'Hospital not found.');
    } elseif ((int) $hospital['booking_count'] > 0) {
        $pdo->prepare('UPDATE users SET status = "inactive" WHERE id = ?')->execute([$hospital['user_id']]);
        flash('success', 'Hospital removed from active listings; booking history was preserved.');
    } else {
        $pdo->prepare('DELETE FROM users WHERE id = ? AND role = "hospital"')->execute([$hospital['user_id']]);
        flash('success', 'Hospital deleted.');
    }
    redirect('admin.hospitals');
}

/** Shows the vaccine catalog and availability at every active hospital. */
function admin_vaccines(PDO $pdo): never
{
    require_role('admin');
    $vaccines = $pdo->query(
        'SELECT v.*, COUNT(DISTINCT CASE WHEN hv.is_available = 1 AND hv.stock_quantity > 0 AND u.status = "active" THEN hv.hospital_id END) available_hospitals,
                COALESCE(SUM(CASE WHEN u.status = "active" THEN hv.stock_quantity ELSE 0 END), 0) total_stock
         FROM vaccines v
         LEFT JOIN hospital_vaccines hv ON hv.vaccine_id = v.id
         LEFT JOIN hospitals h ON h.id = hv.hospital_id
         LEFT JOIN users u ON u.id = h.user_id
         WHERE v.is_active = 1 GROUP BY v.id ORDER BY v.recommended_age_months, v.name, v.dose_label'
    )->fetchAll();
    $availability = $pdo->query(
        'SELECT hu.name hospital_name, h.city, v.name vaccine_name, v.dose_label, hv.is_available, hv.stock_quantity, hv.updated_at
         FROM hospital_vaccines hv
         JOIN hospitals h ON h.id = hv.hospital_id JOIN users hu ON hu.id = h.user_id
         JOIN vaccines v ON v.id = hv.vaccine_id WHERE hu.status = "active" AND v.is_active = 1
         ORDER BY hu.name, v.recommended_age_months, v.name'
    )->fetchAll();
    render('admin/vaccines', ['title' => 'Vaccine availability', 'vaccines' => $vaccines, 'availability' => $availability]);
}

/** Lists all bookings across the complete parent-admin-hospital workflow. */
function admin_bookings(PDO $pdo): never
{
    require_role('admin');
    $status = (string) ($_GET['status'] ?? 'all');
    $allowed = ['all', 'pending', 'approved', 'rejected', 'completed', 'not_vaccinated'];
    if (!in_array($status, $allowed, true)) $status = 'all';
    $sql = 'SELECT a.*, c.name child_name, p.name parent_name, hu.name hospital_name, v.name vaccine_name, v.dose_label
            FROM appointments a JOIN children c ON c.id = a.child_id JOIN users p ON p.id = a.parent_id
            JOIN hospitals h ON h.id = a.hospital_id JOIN users hu ON hu.id = h.user_id JOIN vaccines v ON v.id = a.vaccine_id';
    $parameters = [];
    if ($status !== 'all') { $sql .= ' WHERE a.status = ?'; $parameters[] = $status; }
    $sql .= ' ORDER BY a.appointment_date DESC, a.appointment_time DESC';
    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);
    render('admin/bookings', ['title' => 'Booking details', 'bookings' => $statement->fetchAll(), 'status' => $status]);
}

/** Produces the required child/vaccination date-wise report. */
function admin_reports(PDO $pdo): never
{
    require_role('admin');
    $from = (string) ($_GET['from'] ?? date('Y-m-01'));
    $to = (string) ($_GET['to'] ?? date('Y-m-d'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');
    $statement = $pdo->prepare(
        'SELECT vr.*, c.name child_name, c.date_of_birth, p.name parent_name,
                v.name vaccine_name, v.dose_label, hu.name hospital_name
         FROM vaccination_records vr
         JOIN children c ON c.id = vr.child_id JOIN users p ON p.id = c.parent_id
         JOIN vaccines v ON v.id = vr.vaccine_id
         JOIN hospitals h ON h.id = vr.hospital_id JOIN users hu ON hu.id = h.user_id
         WHERE DATE(vr.administered_at) BETWEEN ? AND ? ORDER BY vr.administered_at DESC'
    );
    $statement->execute([$from, $to]);
    $records = $statement->fetchAll();
    render('admin/reports', ['title' => 'Date-wise vaccination report', 'records' => $records, 'from' => $from, 'to' => $to]);
}
