<?php
declare(strict_types=1);

/**
 * Idempotent database installer for XAMPP/MySQL.
 * Run from the project root with: php setup.php
 */
$config = require __DIR__ . '/app/config.php';
$db = $config['db'];

try {
    $serverDsn = sprintf('mysql:host=%s;port=%d;charset=%s', $db['host'], $db['port'], $db['charset']);
    $server = new PDO($serverDsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // The database identifier is validated before being interpolated because MySQL cannot bind it.
    if (!preg_match('/^[A-Za-z0-9_]+$/', $db['name'])) {
        throw new RuntimeException('DB_DATABASE may contain only letters, numbers, and underscores.');
    }
    $server->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        $db['name']
    ));
    $server->exec('USE `' . $db['name'] . '`');
    $server->exec((string) file_get_contents(__DIR__ . '/database/schema.sql'));

    $server->beginTransaction();

    // Required vaccine schedule used to calculate each child's due dates.
    $vaccines = [
        ['BCG', 'Tuberculosis', 'Birth dose', 0, 'Recommended at birth.'],
        ['OPV', 'Polio', 'Birth dose', 0, 'Oral polio vaccine birth dose.'],
        ['Pentavalent', 'Diphtheria, tetanus, pertussis, hepatitis B and Hib', 'Dose 1', 2, 'First infant dose.'],
        ['PCV', 'Pneumococcal disease', 'Dose 1', 2, 'First pneumococcal dose.'],
        ['OPV', 'Polio', 'Dose 1', 2, 'First scheduled oral polio dose.'],
        ['Pentavalent', 'Diphtheria, tetanus, pertussis, hepatitis B and Hib', 'Dose 2', 3, 'Second infant dose.'],
        ['PCV', 'Pneumococcal disease', 'Dose 2', 3, 'Second pneumococcal dose.'],
        ['OPV', 'Polio', 'Dose 2', 3, 'Second scheduled oral polio dose.'],
        ['Pentavalent', 'Diphtheria, tetanus, pertussis, hepatitis B and Hib', 'Dose 3', 4, 'Third infant dose.'],
        ['PCV', 'Pneumococcal disease', 'Dose 3', 4, 'Third pneumococcal dose.'],
        ['IPV', 'Polio', 'Dose 1', 4, 'First injectable polio dose.'],
        ['Measles-Rubella', 'Measles and rubella', 'Dose 1', 9, 'First measles-rubella dose.'],
        ['Typhoid Conjugate', 'Typhoid', 'Single dose', 9, 'Routine typhoid conjugate dose.'],
        ['Measles-Rubella', 'Measles and rubella', 'Dose 2', 15, 'Second measles-rubella dose.'],
        ['IPV', 'Polio', 'Dose 2', 15, 'Second injectable polio dose.'],
    ];
    $vaccineStatement = $server->prepare(
        'INSERT INTO vaccines (name, disease, dose_label, recommended_age_months, description)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE disease = VALUES(disease), description = VALUES(description), is_active = 1'
    );
    foreach ($vaccines as $vaccine) {
        $vaccineStatement->execute($vaccine);
    }

    // Demo accounts make every required role immediately testable by an evaluator.
    $accounts = [
        ['admin', 'System Administrator', 'admin@vaxcare.test', 'Admin@123', '+92 300 0000001'],
        ['parent', 'Ayesha Khan', 'parent@vaxcare.test', 'Parent@123', '+92 300 0000002'],
        ['hospital', 'Sunrise Children Hospital', 'hospital@vaxcare.test', 'Hospital@123', '+92 300 0000003'],
    ];
    $userStatement = $server->prepare(
        'INSERT INTO users (role, name, email, password_hash, phone)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE role = VALUES(role), name = VALUES(name), phone = VALUES(phone), status = "active"'
    );
    foreach ($accounts as [$role, $name, $email, $password, $phone]) {
        $userStatement->execute([$role, $name, $email, password_hash($password, PASSWORD_DEFAULT), $phone]);
    }

    // Resolve stable IDs after the idempotent account upserts.
    $userLookup = $server->prepare('SELECT id FROM users WHERE email = ?');
    $userLookup->execute(['parent@vaxcare.test']);
    $parentId = (int) $userLookup->fetchColumn();
    $userLookup->execute(['hospital@vaxcare.test']);
    $hospitalUserId = (int) $userLookup->fetchColumn();
    $userLookup->execute(['admin@vaxcare.test']);
    $adminId = (int) $userLookup->fetchColumn();

    $server->prepare(
        'INSERT INTO parent_profiles (user_id, address) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE address = VALUES(address)'
    )->execute([$parentId, 'Gulshan-e-Iqbal, Karachi']);

    $server->prepare(
        'INSERT INTO hospitals (user_id, address, city, location_details) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE address = VALUES(address), city = VALUES(city), location_details = VALUES(location_details)'
    )->execute([$hospitalUserId, '12 Community Road', 'Karachi', 'Near Central Park']);
    $hospitalLookup = $server->prepare('SELECT id FROM hospitals WHERE user_id = ?');
    $hospitalLookup->execute([$hospitalUserId]);
    $hospitalId = (int) $hospitalLookup->fetchColumn();

    $childStatement = $server->prepare(
        'INSERT INTO children (parent_id, name, date_of_birth, gender, blood_group, medical_notes)
         SELECT ?, ?, ?, ?, ?, ? FROM DUAL
         WHERE NOT EXISTS (SELECT 1 FROM children WHERE parent_id = ? AND name = ?)'
    );
    $birthDate = (new DateTimeImmutable('today'))->modify('-8 months')->format('Y-m-d');
    $childStatement->execute([$parentId, 'Hana Khan', $birthDate, 'female', 'O+', 'No known allergies.', $parentId, 'Hana Khan']);
    $childLookup = $server->prepare('SELECT id FROM children WHERE parent_id = ? AND name = ? LIMIT 1');
    $childLookup->execute([$parentId, 'Hana Khan']);
    $childId = (int) $childLookup->fetchColumn();

    // Seed hospital availability for all catalogued vaccines.
    $server->prepare(
        'INSERT INTO hospital_vaccines (hospital_id, vaccine_id, is_available, stock_quantity)
         SELECT ?, id, 1, 20 FROM vaccines
         ON DUPLICATE KEY UPDATE is_available = VALUES(is_available), stock_quantity = GREATEST(stock_quantity, 1)'
    )->execute([$hospitalId]);

    // Representative bookings demonstrate pending, approved, and completed reports.
    $vaccineIds = $server->query('SELECT id FROM vaccines ORDER BY recommended_age_months, id LIMIT 3')->fetchAll(PDO::FETCH_COLUMN);
    $appointmentStatement = $server->prepare(
        'INSERT INTO appointments
         (parent_id, child_id, hospital_id, vaccine_id, appointment_date, appointment_time, reason, status, approved_by, approved_at, completed_at)
         SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? FROM DUAL
         WHERE NOT EXISTS (SELECT 1 FROM appointments WHERE child_id = ? AND vaccine_id = ? AND appointment_date = ?)'
    );
    $samples = [
        [(int) $vaccineIds[0], date('Y-m-d', strtotime('-5 months')), '10:00:00', 'Routine vaccination', 'completed'],
        [(int) $vaccineIds[1], date('Y-m-d', strtotime('+4 days')), '11:30:00', 'Next scheduled dose', 'approved'],
        [(int) $vaccineIds[2], date('Y-m-d', strtotime('+8 days')), '09:30:00', 'Routine appointment request', 'pending'],
    ];
    foreach ($samples as [$vaccineId, $date, $time, $reason, $status]) {
        $approvedBy = $status === 'pending' ? null : $adminId;
        $approvedAt = $status === 'pending' ? null : date('Y-m-d H:i:s');
        $completedAt = $status === 'completed' ? $date . ' ' . $time : null;
        $appointmentStatement->execute([
            $parentId, $childId, $hospitalId, $vaccineId, $date, $time, $reason, $status,
            $approvedBy, $approvedAt, $completedAt, $childId, $vaccineId, $date,
        ]);
    }

    // Convert the completed appointment into the parent's vaccination report.
    $completed = $server->prepare(
        'SELECT id, child_id, vaccine_id, hospital_id, completed_at FROM appointments
         WHERE child_id = ? AND status = "completed" ORDER BY id LIMIT 1'
    );
    $completed->execute([$childId]);
    if ($row = $completed->fetch()) {
        $server->prepare(
            'INSERT INTO vaccination_records
             (appointment_id, child_id, vaccine_id, hospital_id, administered_at, batch_number, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE administered_at = VALUES(administered_at), batch_number = VALUES(batch_number)'
        )->execute([$row['id'], $row['child_id'], $row['vaccine_id'], $row['hospital_id'], $row['completed_at'], 'DEMO-001', 'Vaccination completed without complications.']);
    }

    $server->commit();
    echo "VaxCare setup completed successfully.\n";
    echo "Database: {$db['name']}\n";
    echo "Start: php -S localhost:8000 -t public\n";
    echo "Admin: admin@vaxcare.test / Admin@123\n";
    echo "Parent: parent@vaxcare.test / Parent@123\n";
    echo "Hospital: hospital@vaxcare.test / Hospital@123\n";
} catch (Throwable $exception) {
    if (isset($server) && $server instanceof PDO && $server->inTransaction()) {
        $server->rollBack();
    }
    fwrite(STDERR, "Setup failed: {$exception->getMessage()}\n");
    exit(1);
}
