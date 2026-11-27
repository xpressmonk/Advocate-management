<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\{AuthController,DashboardController,ClientController,AdvocateController,CaseController,HearingController,DocumentController,TaskController,CalendarController,InvoiceController,ExpenseController,ReportController,SettingController,UserController,AuditController,RoleController};
Route::get('/',fn()=>redirect()->route('dashboard'));
Route::middleware('guest')->group(function(){Route::get('/login',[AuthController::class,'show'])->name('login');Route::post('/login',[AuthController::class,'login'])->name('login.store');});
Route::post('/logout',[AuthController::class,'logout'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function(){
 Route::middleware('permission:manage_settings')->group(function(){
  Route::get('/settings/google-drive',[GoogleDriveController::class,'settings'])->name('settings.google-drive');
  Route::get('/settings/google-drive/connect',[GoogleDriveController::class,'connect'])->name('settings.google-drive.connect');
  Route::get('/settings/google-drive/callback',[GoogleDriveController::class,'callback'])->name('settings.google-drive.callback');
 });

 Route::get('/dashboard', DashboardController::class)->name('dashboard');
 Route::resource('clients',ClientController::class);
 Route::resource('advocates',AdvocateController::class);
 Route::resource('cases',CaseController::class);
 Route::resource('hearings',HearingController::class);
 Route::post('/hearings/{hearing}/reminders',[HearingController::class,'configureReminders'])->name('hearings.reminders.configure');
 Route::post('/hearings/{hearing}/reminders/{reminder}/send',[HearingController::class,'sendReminder'])->name('hearings.reminders.send');
 Route::get('/documents',[DocumentController::class,'index'])->middleware('permission:view')->name('documents.index');
 Route::post('/documents',[DocumentController::class,'store'])->middleware('permission:create')->name('documents.store');
 Route::delete('/documents/{document}',[DocumentController::class,'destroy'])->middleware('permission:delete')->name('documents.destroy');
 Route::get('/documents/{document}/download',[DocumentController::class,'download'])->middleware('permission:view')->name('documents.download');
 Route::resource('tasks',TaskController::class);
 Route::get('/calendar',[CalendarController::class,'index'])->name('calendar.index');
 Route::resource('invoices',InvoiceController::class);
 Route::post('/invoices/{invoice}/payments',[InvoiceController::class,'storePayment'])->name('invoices.payments.store');
 Route::delete('/invoices/{invoice}/payments/{payment}',[InvoiceController::class,'destroyPayment'])->name('invoices.payments.destroy');
 Route::resource('expenses',ExpenseController::class);
 Route::get('/reports/invoices/{invoice}/pdf',[ReportController::class,'invoice'])->name('reports.invoices.pdf');
 Route::get('/reports/invoices/{invoice}/receipts/{payment}',[ReportController::class,'receipt'])->name('reports.receipts.pdf');
 Route::get('/reports/cases/{case}/pdf',[ReportController::class,'caseSummary'])->name('reports.cases.pdf');
 Route::get('/reports/expenses/pdf',[ReportController::class,'expenseReport'])->name('reports.expenses.pdf');
 Route::get('/settings/reminders',[SettingController::class,'edit'])->middleware('permission:manage_settings')->name('settings.reminders.edit');
 Route::put('/settings/reminders',[SettingController::class,'update'])->middleware('permission:manage_settings')->name('settings.reminders.update');
 Route::middleware('permission:manage_users')->group(function(){
  Route::post('/users',[UserController::class,'store'])->name('users.store');
  Route::get('/users',[UserController::class,'index'])->name('users.index');
  Route::put('/users/{user}',[UserController::class,'update'])->name('users.update');
  Route::delete('/users/{user}',[UserController::class,'destroy'])->name('users.destroy');
  Route::resource('roles',RoleController::class)->only(['index','store','update','destroy']);
 });
 Route::get('/audit',[AuditController::class,'index'])->middleware('permission:view_audit')->name('audit.index');
});
