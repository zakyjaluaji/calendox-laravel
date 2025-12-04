<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\SyncController;

Route::get('/', [CalendarController::class, 'index'])->name('calendar');

Route::post('/appointments', [AppointmentController::class, 'store']);
Route::post('/appointments/{id}', [AppointmentController::class, 'update']);
Route::post('/appointments/{id}/delete', [AppointmentController::class, 'destroy']);

Route::post('/participants/add', [ParticipantController::class, 'add']);
Route::post('/participants/list', [ParticipantController::class, 'list']);
Route::post('/participants/delete', [ParticipantController::class, 'delete']);
Route::post('/participants/invite', [ParticipantController::class, 'invite']);
Route::post('/pic/search', [ParticipantController::class, 'searchPic']);

Route::get('/google/auth', [GoogleController::class, 'auth']);
Route::get('/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
Route::post('/google/sync-two-way', [SyncController::class, 'twoWay']);
Route::post('/google/sync-one', [SyncController::class, 'one']);
