<?php
declare(strict_types=1);

/** Shows each child's calculated due dates and approved appointments. */
function parent_dashboard(PDO $pdo): never
{
    $parent = require_role('parent');
    $childrenStatement = $pdo->prepare(
        'SELECT c.*, (SELECT COUNT(*) FROM vaccination_records vr WHERE vr.child_id = c.id) completed_doses
         FROM children c WHERE c.parent_id = ? ORDER BY c.name'
    );
    $childrenStatement->execute([$parent['id']]);
    $children = $childrenStatement->fetchAll();

    $scheduleStatement = $pdo->prepare(
        'SELECT c.id child_id, c.name child_name, v.id vaccine_id, v.name vaccine_name, v.dose_label,
                DATE_ADD(c.date_of_birth, INTERVAL v.recommended_age_months MONTH) due_date,
                EXISTS(SELECT 1 FROM appointments a WHERE a.child_id = c.id AND a.vaccine_id = v.id AND a.status IN ("pending", "approved")) is_booked
         FROM children c CROSS JOIN vaccines v
         WHERE c.parent_id = ? AND v.is_active = 1
           AND NOT EXISTS (SELECT 1 FROM vaccination_records vr WHERE vr.child_id = c.id AND vr.vaccine_id = v.id)
         ORDER BY due_date, c.name LIMIT 12'
    );
    $scheduleStatement->execute([$parent['id']]);
    $schedule = $scheduleStatement->fetchAll();

    $appointmentsStatement = $pdo->prepare(
        'SELECT a.*, c.name child_name, v.name vaccine_name, v.dose_label, hu.name hospital_name
         FROM appointments a JOIN children c ON c.id = a.child_id JOIN vaccines v ON v.id = a.vaccine_id
         JOIN hospitals h ON h.id = a.hospital_id JOIN users hu ON hu.id = h.user_id
         WHERE a.parent_id = ? AND a.appointment_date >= CURDATE() AND a.status IN ("pending", "approved")
         ORDER BY a.appointment_date, a.appointment_time LIMIT 6'
    );
    $appointmentsStatement->execute([$parent['id']]);
    render('parent/dashboard', [
        'title' => 'Parent dashboard', 'children' => $children,
        'schedule' => $schedule, 'appointments' => $appointmentsStatement->fetchAll(),
    ]);
}

/** Lists child profiles and opens the create/update form. */
function parent_children(PDO $pdo): never
{
    $parent = require_role('parent');
    $statement = $pdo->prepare(
        'SELECT c.*, (SELECT COUNT(*) FROM vaccination_records vr WHERE vr.child_id = c.id) completed_doses,
                (SELECT COUNT(*) FROM appointments a WHERE a.child_id = c.id) booking_count
         FROM children c WHERE c.parent_id = ? ORDER BY c.name'
    );
    $statement->execute([$parent['id']]);
    $editing = null;
    if (!empty($_GET['edit'])) {
        $lookup = $pdo->prepare('SELECT * FROM children WHERE id = ? AND parent_id = ?');
        $lookup->execute([(int) $_GET['edit'], $parent['id']]);
        $editing = $lookup->fetch() ?: null;
    }
    render('parent/children', ['title' => 'My children', 'children' => $statement->fetchAll(), 'editing' => $editing]);
}

/** Creates or updates a child belonging to the signed-in parent. */
function parent_child_save(PDO $pdo): never
{
    $parent = require_role('parent');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
    verify_csrf();
    $childId = (int) input('child_id');
    $name = input('name');
    $dateOfBirth = input('date_of_birth');
    $gender = input('gender');
    $bloodGroup = input('blood_group');
    $notes = input('medical_notes');
    $validDate = DateTimeImmutable::createFromFormat('Y-m-d', $dateOfBirth);

    if (mb_strlen($name) < 2 || !$validDate || $dateOfBirth > date('Y-m-d') || !in_array($gender, ['female', 'male', 'other'], true)) {
        flash('error', 'Enter a valid child name, birth date, and gender.');
        redirect('parent.children', $childId ? ['edit' => $childId] : []);
    }
    if ($childId) {
        $statement = $pdo->prepare(
            'UPDATE children SET name = ?, date_of_birth = ?, gender = ?, blood_group = ?, medical_notes = ?
             WHERE id = ? AND parent_id = ?'
        );
        $statement->execute([$name, $dateOfBirth, $gender, $bloodGroup ?: null, $notes ?: null, $childId, $parent['id']]);
        flash($statement->rowCount() ? 'success' : 'warning', $statement->rowCount() ? 'Child details updated.' : 'No changes were needed.');
    } else {
        $pdo->prepare(
            'INSERT INTO children (parent_id, name, date_of_birth, gender, blood_group, medical_notes) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$parent['id'], $name, $dateOfBirth, $gender, $bloodGroup ?: null, $notes ?: null]);
        flash('success', 'Child added. Vaccination dates are now calculated on the dashboard.');
    }
    redirect('parent.children');
}

/** Deletes only a child owned by the current parent. */
function parent_child_delete(PDO $pdo): never
{
    $parent = require_role('parent');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
    verify_csrf();
    $statement = $pdo->prepare('DELETE FROM children WHERE id = ? AND parent_id = ?');
    $statement->execute([(int) input('child_id'), $parent['id']]);
    flash($statement->rowCount() ? 'success' : 'warning', $statement->rowCount() ? 'Child record deleted.' : 'Child record not found.');
    redirect('parent.children');
}

/** Searches active hospitals and displays their available vaccines for booking. */
function parent_hospitals(PDO $pdo): never
{
    $parent = require_role('parent');
    $search = trim((string) ($_GET['search'] ?? ''));
    $sql = 'SELECT h.*, u.name, u.email, u.phone,
                   GROUP_CONCAT(DISTINCT CONCAT(v.name, " — ", v.dose_label) ORDER BY v.recommended_age_months SEPARATOR "||") available_vaccines,
                   COUNT(DISTINCT CASE WHEN hv.is_available = 1 AND hv.stock_quantity > 0 THEN hv.vaccine_id END) available_count
            FROM hospitals h JOIN users u ON u.id = h.user_id
            LEFT JOIN hospital_vaccines hv ON hv.hospital_id = h.id AND hv.is_available = 1 AND hv.stock_quantity > 0
            LEFT JOIN vaccines v ON v.id = hv.vaccine_id AND v.is_active = 1
            WHERE u.status = "active"';
    $parameters = [];
    if ($search !== '') {
        $sql .= ' AND (u.name LIKE ? OR h.city LIKE ? OR h.address LIKE ?)';
        $term = '%' . $search . '%';
        $parameters = [$term, $term, $term];
    }
    $sql .= ' GROUP BY h.id ORDER BY u.name';
    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);
    $childStatement = $pdo->prepare('SELECT id, name, date_of_birth FROM children WHERE parent_id = ? ORDER BY name');
    $childStatement->execute([$parent['id']]);
    $vaccines = $pdo->query('SELECT id, name, dose_label, recommended_age_months FROM vaccines WHERE is_active = 1 ORDER BY recommended_age_months, name')->fetchAll();
    render('parent/hospitals', [
        'title' => 'Book a hospital', 'hospitals' => $statement->fetchAll(),
        'children' => $childStatement->fetchAll(), 'vaccines' => $vaccines, 'search' => $search,
    ]);
}

/** Creates a pending appointment only when the selected hospital reports stock. */
function parent_booking_create(PDO $pdo): never
{
    $parent = require_role('parent');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
    verify_csrf();
    $childId = (int) input('child_id');
    $hospitalId = (int) input('hospital_id');
    $vaccineId = (int) input('vaccine_id');
    $date = input('appointment_date');
    $time = input('appointment_time');
    $reason = input('reason');

    if ($date < date('Y-m-d') || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
        flash('error', 'Choose a valid future appointment date and time.');
        redirect('parent.hospitals');
    }
    $eligibility = $pdo->prepare(
        'SELECT c.id
         FROM children c
         JOIN hospitals h ON h.id = ? JOIN users hu ON hu.id = h.user_id AND hu.status = "active"
         JOIN hospital_vaccines hv ON hv.hospital_id = h.id AND hv.vaccine_id = ? AND hv.is_available = 1 AND hv.stock_quantity > 0
         JOIN vaccines v ON v.id = hv.vaccine_id AND v.is_active = 1
         WHERE c.id = ? AND c.parent_id = ?'
    );
    $eligibility->execute([$hospitalId, $vaccineId, $childId, $parent['id']]);
    if (!$eligibility->fetchColumn()) {
        flash('error', 'That child, hospital, or available vaccine selection is no longer valid.');
        redirect('parent.hospitals');
    }
    $duplicate = $pdo->prepare(
        'SELECT COUNT(*) FROM appointments WHERE child_id = ? AND vaccine_id = ? AND status IN ("pending", "approved", "completed")'
    );
    $duplicate->execute([$childId, $vaccineId]);
    if ((int) $duplicate->fetchColumn() > 0) {
        flash('warning', 'This vaccine dose already has an active or completed booking for the child.');
        redirect('parent.bookings');
    }
    $pdo->prepare(
        'INSERT INTO appointments (parent_id, child_id, hospital_id, vaccine_id, appointment_date, appointment_time, reason)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([$parent['id'], $childId, $hospitalId, $vaccineId, $date, $time . ':00', $reason ?: null]);
    flash('success', 'Appointment request sent to the administrator for approval.');
    redirect('parent.bookings');
}

/** Lists all booking requests made by the current parent. */
function parent_bookings(PDO $pdo): never
{
    $parent = require_role('parent');
    $statement = $pdo->prepare(
        'SELECT a.*, c.name child_name, v.name vaccine_name, v.dose_label, hu.name hospital_name, h.city
         FROM appointments a JOIN children c ON c.id = a.child_id JOIN vaccines v ON v.id = a.vaccine_id
         JOIN hospitals h ON h.id = a.hospital_id JOIN users hu ON hu.id = h.user_id
         WHERE a.parent_id = ? ORDER BY a.appointment_date DESC, a.created_at DESC'
    );
    $statement->execute([$parent['id']]);
    render('parent/bookings', ['title' => 'My bookings', 'bookings' => $statement->fetchAll()]);
}

/** Shows the parent's completed vaccination report for all children. */
function parent_report(PDO $pdo): never
{
    $parent = require_role('parent');
    $statement = $pdo->prepare(
        'SELECT vr.*, c.name child_name, c.date_of_birth, v.name vaccine_name, v.dose_label, v.disease,
                hu.name hospital_name
         FROM vaccination_records vr JOIN children c ON c.id = vr.child_id
         JOIN vaccines v ON v.id = vr.vaccine_id JOIN hospitals h ON h.id = vr.hospital_id
         JOIN users hu ON hu.id = h.user_id WHERE c.parent_id = ?
         ORDER BY c.name, vr.administered_at DESC'
    );
    $statement->execute([$parent['id']]);
    render('parent/report', ['title' => 'Vaccination report', 'records' => $statement->fetchAll()]);
}

/** Displays and updates the parent's required profile information. */
function parent_profile(PDO $pdo): never
{
    $parent = require_role('parent');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $name = input('name');
        $email = strtolower(input('email'));
        $phone = input('phone');
        $address = input('address');
        $password = input('password');
        if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($address) < 5) {
            flash('error', 'Enter a valid name, email, and address.');
            redirect('parent.profile');
        }
        try {
            $pdo->beginTransaction();
            $sql = 'UPDATE users SET name = ?, email = ?, phone = ?';
            $parameters = [$name, $email, $phone ?: null];
            if ($password !== '') {
                if (strlen($password) < 8) throw new RuntimeException('New password must contain at least 8 characters.');
                $sql .= ', password_hash = ?';
                $parameters[] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = ?';
            $parameters[] = $parent['id'];
            $pdo->prepare($sql)->execute($parameters);
            $pdo->prepare('UPDATE parent_profiles SET address = ? WHERE user_id = ?')->execute([$address, $parent['id']]);
            $pdo->commit();
            refresh_user($pdo, (int) $parent['id']);
            flash('success', 'Profile updated.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('error', $exception instanceof PDOException && $exception->getCode() === '23000' ? 'That email is already in use.' : $exception->getMessage());
        }
        redirect('parent.profile');
    }
    $statement = $pdo->prepare('SELECT u.*, pp.address FROM users u JOIN parent_profiles pp ON pp.user_id = u.id WHERE u.id = ?');
    $statement->execute([$parent['id']]);
    render('parent/profile', ['title' => 'My profile', 'profile' => $statement->fetch()]);
}
