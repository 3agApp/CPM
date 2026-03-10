<?php

use App\Http\Controllers\InvitationAcceptController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/invitations/accept/{token}', InvitationAcceptController::class)
    ->name('invitation.accept');
