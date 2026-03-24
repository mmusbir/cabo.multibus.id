<?php
ob_start();
/**
 * admin/ajax.php - Centralized AJAX router for admin module
 * 
 * Usage: All AJAX calls should go to admin/ajax.php?action=xxx
 * This allows AJAX requests to load ONLY what they need, not the entire admin.php
 */

require_once __DIR__ . '/init.php';

// Require authentication for all AJAX calls
requireAdminAuth();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    // Data Fetching
    case 'getSchedules':
        include __DIR__ . '/ajax/schedules.php';
        break;

    case 'getPassengers':
        include __DIR__ . '/ajax/passengers.php';
        break;

    case 'assignDriver':
        include __DIR__ . '/ajax/assign_driver.php';
        break;

    case 'getAvailableUnits':
        include __DIR__ . '/ajax/get_available_units.php';
        break;

    case 'getScheduleSeats':
        include __DIR__ . '/ajax/get_schedule_seats.php';
        break;

    case 'exportReportCsv':
        include __DIR__ . '/ajax/export_report_csv.php';
        break;

    // Page Listings
    case 'customersPage':
        include __DIR__ . '/ajax/customers.php';
        break;

    case 'bookingsPage':
        include __DIR__ . '/ajax/bookings.php';
        break;

    case 'chartersPage':
        include __DIR__ . '/ajax/charters.php';
        break;

    case 'schedulesPage':
        include __DIR__ . '/ajax/schedules_page.php';
        break;

    case 'usersPage':
        include __DIR__ . '/ajax/users.php';
        break;

    case 'cancellationsPage':
        include __DIR__ . '/ajax/cancellations.php';
        break;

    case 'luggagePage':
        include __DIR__ . '/ajax/luggage_page.php';
        break;

    case 'luggageDataPage':
        include __DIR__ . '/ajax/luggage_data_page.php';
        break;

    case 'reportsPage':
        include __DIR__ . '/ajax/reports.php';
        break;

    case 'luggageServicesPage':
        include __DIR__ . '/ajax/luggage_services_page.php';
        break;

    case 'luggageServiceCRUD':
        include __DIR__ . '/ajax/luggage_service_crud.php';
        break;

    case 'routesPage':
        include __DIR__ . '/ajax/routes.php';
        break;

    case 'changePassword':
        include __DIR__ . '/ajax/change_password.php';
        break;

    // Charter CRUD
    case 'delete_charter':
    case 'get_charter':
    case 'update_charter':
    case 'toggle_bop':
    case 'get_units':
    case 'get_charter_routes':
    case 'get_drivers':
        include __DIR__ . '/ajax/charter_crud.php';
        break;

    // Luggage Actions
    case 'markLuggagePaid':
    case 'cancelLuggage':
    case 'inputLuggage':
    case 'inputLuggageRaw':
    case 'updateLuggageSimple':
        include __DIR__ . '/ajax/luggage_actions.php';
        break;

    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'unknown_action']);
        exit;
}
