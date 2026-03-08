<?php

use App\Http\Controllers\InvitationAcceptController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/invitations/accept/{token}', InvitationAcceptController::class)
    ->name('invitation.accept');
