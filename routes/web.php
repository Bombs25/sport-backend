<?php

use App\Http\Controllers\Auth\VerifyEmailFromSignedUrlController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
| Ce qu’il fait : route signée utilisée par le **lien de vérification email** (notification Laravel).
| Pourquoi : les mails ouvrent un navigateur ; ce n’est pas une route API Bearer ; le nom `verification.verify`
| est attendu par `MustVerifyEmail` pour générer l’URL.
*/
Route::get('/email/verify/{id}/{hash}', VerifyEmailFromSignedUrlController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
