<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — ComplySmart Application Views
|--------------------------------------------------------------------------
*/

// ── Main Page Routes ────────────────────────────────-----------------------
Route::get('/',                 fn () => view('pages.welcome'));
Route::get('/welcome',          fn () => view('pages.welcome'));
Route::get('/login',            fn () => view('pages.login'));
Route::get('/register',         fn () => view('pages.register'));
Route::get('/dashboard',        fn () => view('pages.dashboard'));
Route::get('/profile',          fn () => view('pages.profile'));
Route::get('/documents',        fn () => view('pages.documents'));
Route::get('/documents/upload', fn () => view('pages.upload_document'));
Route::get('/documents/details',fn () => view('pages.document_details'));
Route::get('/tasks',            fn () => view('pages.tasks'));
Route::get('/renewals',         fn () => view('test.renewals'));
Route::get('/notifications',    fn () => view('test.notifications'));
Route::get('/ai',               fn () => view('test.ai'));
Route::get('/reports',          fn () => view('test.reports'));

// ── Legacy / Test Frontend Routes ─────────────────────────────────────────
Route::prefix('test')->group(function () {
    Route::get('/',               fn () => view('pages.dashboard'));
    Route::get('/auth',           fn () => view('pages.login'));
    Route::get('/businesses',     fn () => view('pages.profile'));
    Route::get('/documents',      fn () => view('pages.documents'));
    Route::get('/tasks',          fn () => view('pages.tasks'));
    Route::get('/renewals',       fn () => view('test.renewals'));
    Route::get('/notifications',  fn () => view('test.notifications'));
    Route::get('/ai',             fn () => view('test.ai'));
    Route::get('/reports',        fn () => view('test.reports'));
});
