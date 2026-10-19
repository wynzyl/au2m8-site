<?php

use App\Http\Controllers\Admin\CompanyProfileController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PageController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/projects', [PageController::class, 'projects'])->name('projects');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/company', [CompanyProfileController::class, 'edit'])->name('company.edit');
        Route::put('/company', [CompanyProfileController::class, 'update'])->name('company.update');

        $projectMissing = fn (): RedirectResponse => redirect()
            ->route('admin.projects.index')
            ->with('status', 'That project no longer exists.');

        Route::resource('projects', ProjectController::class)->except('show')->missing($projectMissing);
        Route::get('/projects/{project}/delete', [ProjectController::class, 'delete'])
            ->name('projects.delete')
            ->missing($projectMissing);
    });
});
