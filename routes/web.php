<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AssistantController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', \App\Livewire\DocBOt::class);

Route::get('/files', [AssistantController::class, 'index'])->name('files');
Route::post('/files', [AssistantController::class, 'upload'])->name('files.store');
Route::post('/send', [AssistantController::class, 'send'])->name('send');
