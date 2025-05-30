<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Hospital Management System API Routes
Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/profile', [App\Http\Controllers\Api\AuthController::class, 'profile']);
        
        // Patient routes
        Route::apiResource('patients', App\Http\Controllers\Api\PatientController::class);
        Route::get('patients/{patient}/medical-records', [App\Http\Controllers\Api\PatientController::class, 'medicalRecords']);
        Route::get('patients/{patient}/appointments', [App\Http\Controllers\Api\PatientController::class, 'appointments']);
        Route::get('patients/{patient}/prescriptions', [App\Http\Controllers\Api\PatientController::class, 'prescriptions']);
        Route::get('patients/{patient}/bills', [App\Http\Controllers\Api\PatientController::class, 'bills']);
        
        // Appointment routes
        Route::apiResource('appointments', App\Http\Controllers\Api\AppointmentController::class);
        Route::patch('appointments/{appointment}/status', [App\Http\Controllers\Api\AppointmentController::class, 'updateStatus']);
        
        // Doctor routes
        Route::apiResource('doctors', App\Http\Controllers\Api\DoctorController::class);
        Route::get('doctors/{doctor}/appointments', [App\Http\Controllers\Api\DoctorController::class, 'appointments']);
        Route::get('doctors/{doctor}/schedule', [App\Http\Controllers\Api\DoctorController::class, 'schedule']);
        
        // Medical Records routes
        Route::apiResource('medical-records', App\Http\Controllers\Api\MedicalRecordController::class);
        
        // Prescription routes
        Route::apiResource('prescriptions', App\Http\Controllers\Api\PrescriptionController::class);
        
        // Laboratory routes
        Route::apiResource('lab-tests', App\Http\Controllers\Api\LabTestController::class);
        Route::apiResource('lab-results', App\Http\Controllers\Api\LabResultController::class);
        
        // Pharmacy routes
        Route::apiResource('medicines', App\Http\Controllers\Api\MedicineController::class);
        Route::get('pharmacy/stock', [App\Http\Controllers\Api\PharmacyController::class, 'stock']);
        Route::post('pharmacy/dispense', [App\Http\Controllers\Api\PharmacyController::class, 'dispense']);
        
        // Billing routes
        Route::apiResource('bills', App\Http\Controllers\Api\BillController::class);
        Route::post('bills/{bill}/payment', [App\Http\Controllers\Api\BillController::class, 'addPayment']);
        
        // Ward/Room routes
        Route::apiResource('wards', App\Http\Controllers\Api\WardController::class);
        Route::apiResource('rooms', App\Http\Controllers\Api\RoomController::class);
        Route::get('rooms/available', [App\Http\Controllers\Api\RoomController::class, 'available']);
        
        // Emergency routes
        Route::apiResource('emergency-visits', App\Http\Controllers\Api\EmergencyVisitController::class);
        
        // Inventory routes
        Route::apiResource('inventory', App\Http\Controllers\Api\InventoryController::class);
        Route::post('inventory/{item}/stock-update', [App\Http\Controllers\Api\InventoryController::class, 'updateStock']);
        
        // Reports routes
        Route::get('reports/dashboard', [App\Http\Controllers\Api\ReportController::class, 'dashboard']);
        Route::get('reports/revenue', [App\Http\Controllers\Api\ReportController::class, 'revenue']);
        Route::get('reports/patients', [App\Http\Controllers\Api\ReportController::class, 'patients']);
        Route::get('reports/appointments', [App\Http\Controllers\Api\ReportController::class, 'appointments']);
    });
});
