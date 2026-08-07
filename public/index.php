<?php
declare(strict_types=1);

/**
 * Front controller: every browser request is routed through this small map.
 */
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/controllers/AdminController.php';
require_once dirname(__DIR__) . '/app/controllers/ParentController.php';
require_once dirname(__DIR__) . '/app/controllers/HospitalController.php';

$route = (string) ($_GET['route'] ?? 'home');
$routes = [
    'home' => 'auth_home',
    'login' => 'auth_login',
    'register.parent' => 'auth_register_parent',
    'register.hospital' => 'auth_register_hospital',
    'logout' => 'auth_logout',

    'admin.dashboard' => 'admin_dashboard',
    'admin.children' => 'admin_children',
    'admin.requests' => 'admin_requests',
    'admin.request.update' => 'admin_request_update',
    'admin.hospitals' => 'admin_hospitals',
    'admin.hospital.save' => 'admin_hospital_save',
    'admin.hospital.delete' => 'admin_hospital_delete',
    'admin.vaccines' => 'admin_vaccines',
    'admin.bookings' => 'admin_bookings',
    'admin.reports' => 'admin_reports',

    'parent.dashboard' => 'parent_dashboard',
    'parent.children' => 'parent_children',
    'parent.child.save' => 'parent_child_save',
    'parent.child.delete' => 'parent_child_delete',
    'parent.hospitals' => 'parent_hospitals',
    'parent.booking.create' => 'parent_booking_create',
    'parent.bookings' => 'parent_bookings',
    'parent.report' => 'parent_report',
    'parent.profile' => 'parent_profile',

    'hospital.dashboard' => 'hospital_dashboard',
    'hospital.inventory' => 'hospital_inventory',
    'hospital.inventory.update' => 'hospital_inventory_update',
    'hospital.appointments' => 'hospital_appointments',
    'hospital.appointment.update' => 'hospital_appointment_update',
];

if (!isset($routes[$route])) {
    http_response_code(404);
    render('errors/404', ['title' => 'Page not found']);
}

$routes[$route]($pdo);
