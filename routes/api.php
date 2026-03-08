<?php

use App\Http\Controllers\Api\DocumentConversationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/sanctum/token', function (Request $request) {
    $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    $user = \App\Models\User::where('email', $request->email)->first();

    if (! $user || ! \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    return response()->json([
        'token' => $user->createToken('api')->plainTextToken,
    ]);
});

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('/conversations', [DocumentConversationController::class, 'store'])
        ->name('api.conversations.store');

    Route::get('/conversations/{conversation}', [DocumentConversationController::class, 'show'])
        ->name('api.conversations.show');

    Route::post('/conversations/{conversation}/context', [DocumentConversationController::class, 'provideContext'])
        ->name('api.conversations.context');

    Route::get('/conversations/{conversation}/download', [DocumentConversationController::class, 'download'])
        ->name('api.conversations.download');
});
