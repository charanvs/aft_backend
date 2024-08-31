<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PdfController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
Route::middleware('admin')->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'AdminDashboard'])->name('admin.dashboard');

});
Route::get('/admin/login', [AdminController::class, 'AdminLogin'])->name('admin.login');
// Route::get('/admin/dashboard', [AdminController::class, 'AdminDashboard'])->name('admin.login');
Route::post('/admin/login_submit', [AdminController::class, 'AdminLoginSubmit'])->name('admin.login_submit');
Route::get('/admin/logout', [AdminController::class, 'AdminLogout'])->name('admin.logout');

// Route to display the form for uploading PDF
Route::get('/pdf-to-text', function () {
    return view('convert.convert'); // Ensure this matches the Blade file name
})->name('pdf.to.text');

// Route to handle the form submission and process the PDF
Route::post('/convert-pdf-to-text', [PdfController::class, 'convertPdfToText'])->name('convert.pdf.to.text');

Route::get('/get-progress', function (Request $request) {
    $type = $request->get('type');
    $progress = session()->get($type . '_progress', 0);
    return response()->json(['progress' => $progress]);
});


