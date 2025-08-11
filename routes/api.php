<?php
// routes/api.php

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\API\DobApiController;
use App\Http\Controllers\API\AuthAPIController;
use App\Http\Controllers\API\AdminAPIController;
use App\Http\Controllers\API\ShiftApiController;
use App\Http\Controllers\API\TrainingController;
use App\Http\Controllers\API\CheckCallController;
use App\Http\Controllers\API\InvoiceAPIController;
use App\Http\Controllers\API\MessageApiController;
use App\Http\Controllers\API\ProfileAPIController;
use App\Http\Controllers\API\DocumentAPIController;
use App\Http\Controllers\API\LocationAPIController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\IncidentReportController;
use App\Http\Controllers\API\EmergencyAlertAPIController;

// Define your API routes here

Route::get('/shifts', [ShiftController::class, 'getShifts']);
Route::get('/shifts-with-staff', [ShiftController::class, 'getShiftsWithStaff']);
Route::get('/shifts-by-site', [ShiftController::class, 'getShiftsBySite']);
Route::get('/shifts-today', [ShiftController::class, 'getTodayShifts']);

Route::get('/client/{id}', [ShiftController::class, 'getClient']);
Route::get('/staff/{id}', [ShiftController::class, 'getStaff']);

//API routes for authentication api
Route::prefix('auth')->group(function () {
    //Auth controller routes
    Route::post('/login', [AuthAPIController::class, 'login']);
    Route::post('/forgot-password', [AuthAPIController::class, 'forgotPassword']);
    Route::post('/verify-reset-code', [AuthAPIController::class, 'verifyResetCode']);
    Route::post('/reset-password', [AuthAPIController::class, 'resetPassword']);
    Route::post('/face-verify', [AuthApiController::class, 'faceVerify'])->middleware('auth:sanctum');
    Route::post('/refresh-token', [AuthApiController::class, 'refreshToken']);
});

//Profile controller routes
//Should be authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileAPIController::class, 'getProfile']);
    Route::put('/profile', [ProfileAPIController::class, 'updateProfile']);
    Route::post('/profile/face-data', [ProfileAPIController::class, 'uploadFaceData']);
});

//Document controller routes
//Should be authenticated
Route::middleware('auth:sanctum')->prefix('documents')->group(function () {
    Route::post('/', [DocumentAPIController::class, 'upload']);
    Route::get('/', [DocumentAPIController::class, 'index']);
    Route::get('/alerts', [DocumentAPIController::class, 'alerts']);
});

//Shifts api controller routes
//Should be authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/shifts/upcoming', [ShiftApiController::class, 'getUpcomingShifts']);
    Route::post('/shifts/{shift_id}/respond', [ShiftApiController::class, 'respondToShift']);
    Route::post('/leave-requests', [ShiftApiController::class, 'submitLeaveRequest']);
    Route::post('/shifts/{shift_id}/acknowledge-documents', [ShiftApiController::class, 'acknowledgeDocuments']);

    Route::post('/shifts/{shiftDate_id}/book-on', [ShiftApiController::class, 'bookOn']);
    Route::post('/shifts/{shiftDate_id}/book-off', [ShiftApiController::class, 'bookOff']);

    Route::get('/alarms/booking', [ShiftApiController::class, 'getBookingAlarms']);
    Route::post('/alarms/{alarm_id}/acknowledge', [ShiftApiController::class, 'acknowledgeAlarm']);

    Route::get('/shift-status', [ShiftApiController::class, 'checkDutyStatus']);
});

//Shifts api controller routes
//Should be authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/shifts/{shift_id}/check-calls', [CheckCallController::class, 'getCheckCalls']);
    Route::post('/check-calls/{check_call_id}/complete', [CheckCallController::class, 'completeCheckCall']);
    Route::get('/alarms/check-calls', [CheckCallController::class, 'getCheckCallAlarms']);
});
Route::post('/check-calls/phone-complete', [CheckCallController::class, 'phoneComplete']);

//Patrols api controller routes
//Should be authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/shifts/{shift_id}/patrols', [ShiftApiController::class, 'getPatrolRoutes']);
    Route::post('/patrols/checkpoints/{checkpoint_id}/scan', [ShiftApiController::class, 'scanCheckpoint']);
    Route::post('/patrols/{patrol_id}/complete', [ShiftApiController::class, 'completePatrol']);
});

//DOB api controller routes
//Should be authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/dob', [DobApiController::class,'store']);
    Route::get('/dob', [DobApiController::class,'index']);
    Route::put('/dob/{id}', [DobApiController::class,'update']);
});


//Incident Reporting api controller routes
//Should be authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/incidents ', [IncidentReportController::class, 'store']);
    Route::get('/incidents', [IncidentReportController::class, 'index']);
    Route::put('/incidents/{id}', [IncidentReportController::class, 'update']);
});

//Messaging system api controller routes
//Should be authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/messages/conversations', [MessageApiController::class, 'getConversations']);
    Route::get('/messages/conversations/{conversation}', [MessageApiController::class, 'getMessages']);
    Route::post('/messages', [MessageApiController::class, 'sendMessage']);
    Route::post('/messages/mark-read', [MessageApiController::class, 'markRead']);
});

//GPS Tracking api controller routes
//Should be authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/location/history', [LocationAPIController::class, 'history']);
    Route::post('/location/update', [LocationAPIController::class, 'update']);
});

//Emergency/Panic Button api controller routes
//Should be authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/emergency/alert', [EmergencyAlertAPIController::class, 'trigger']);
    Route::post('/emergency/{alert}/cancel', [EmergencyAlertAPIController::class, 'cancel']);
});

//Invoice Managment api controller routes
//Should be authenticated
Route::middleware('auth:sanctum')->prefix('invoices')->group(function () {
    Route::get('/shift-history', [InvoiceAPIController::class, 'shiftHistory']);
    Route::post('/', [InvoiceAPIController::class, 'submitInvoice']);
    Route::get('/', [InvoiceAPIController::class, 'getInvoices']);
    Route::post('{invoice}/confirm-revision', [InvoiceAPIController::class, 'confirmRevision']);
});

//Training & Bulletins Managment api controller routes
//Should be authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/training', [TrainingController::class, 'index']);
    Route::post('/training/{training_id}/acknowledge', [TrainingController::class, 'acknowledge']);
});

//Notifications api controller routes
//Should be authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification_id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/register-device', [NotificationController::class, 'registerDevice']);
    
});

// Route::middleware('auth:sanctum')->post('/notifications/mark-all-read', function () {
//     Notification::where('user_id', auth()->id())->update(['read' => true]);
//     return response()->json(['message' => 'All marked as read']);
// });


Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/alerts/send-to-guard', [AdminAPIController::class, 'sendAlertToGuard']);
});

//Admin endpoint api controller routes
//Should be authenticated
Route::middleware(['auth:sanctum'])->group(function () {
    // 48 done elsewhere
    Route::delete('/admin/messages/{message_id}', [AdminAPIController::class, 'deleteMessage']);
    Route::put('/admin/dob/{entry_id}/edit', [AdminAPIController::class, 'editDOBEntry']);
    Route::post('/admin/alarms/{alarm_id}/override', [AdminAPIController::class, 'overrideMissedAlarm']);
});