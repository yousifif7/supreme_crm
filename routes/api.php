<?php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShiftController;

// Define your API routes here

    Route::get('/api/shifts', [ShiftController::class, 'getShifts']);
    Route::get('/api/shifts-with-staff', [ShiftController::class, 'getShiftsWithStaff']);
    Route::get('/api/shifts-by-site', [ShiftController::class, 'getShiftsBySite']);
    Route::get('/api/shifts-today', [ShiftController::class, 'getTodayShifts']);
