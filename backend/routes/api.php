<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\FolderController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\AdminFolderSetupController;

// Public routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);


// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    Route::middleware('admin')->prefix('admin')->group(function(){
        Route::post('/setup-hr-structure', [AdminFolderSetupController::class, 'setupStructure']);
        Route::post('/add-hr-folder', action: [AdminFolderSetupController::class, 'addFolder']);
        Route::get('/users', [AuthController::class,'user']);
    });

    Route::post('/employee/documents/upload', [DocumentController::class, 'upload']);
    Route::get('/employee/documents/{folder}', [DocumentController::class, 'listByFolder']);
    Route::delete('/employee/document/{category}/{id}', [DocumentController::class, 'delete']);

    Route::post('logout', [AuthController::class, 'logout']);
});







