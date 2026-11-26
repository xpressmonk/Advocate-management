<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController,DashboardController,ClientController,AdvocateController,CaseController,HearingController};
Route::get('/',fn()=>redirect()->route('dashboard'));
Route::middleware('guest')->group(function(){Route::get('/login',[AuthController::class,'show'])->name('login');Route::post('/login',[AuthController::class,'login'])->name('login.store');});
Route::post('/logout',[AuthController::class,'logout'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function(){
 //Route::get('/dashboard',DashboardController::class)->name('dashboard');
 Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
 Route::resource('clients',ClientController::class);
 Route::resource('advocates',AdvocateController::class);
 Route::resource('cases',CaseController::class);
 Route::resource('hearings',HearingController::class);
});
