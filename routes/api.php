<?php

/*
| Ce qu’il fait : point d’entrée des routes API — charge chaque domaine sous `routes/api/v1/`.
|
| Pourquoi : `api.php` reste le parent unique (≤ ~100 lignes) ; pas d’imbrication « teams » dans `auth.php`.
| Le préfixe `/api` vient de `bootstrap/app.php`.
*/
// Auth : login, logout, mot de passe oublié / reset, OTP e-mail, changement e-mail / mot de passe, credentials + pseudo, sports publics.
require __DIR__.'/api/v1/auth.php';
// Inscription : wizard register (localisation, profil, sports) sous `/api/v1/auth/register/...`.
require __DIR__.'/api/v1/register.php';
// Compte / social : utilisateur courant, profil, follow, profil public (`/api/v1/auth/...`, contrôleurs hors namespace Auth).
require __DIR__.'/api/v1/account.php';
// Équipes : CRUD sous `/api/v1/auth/teams...`.
require __DIR__.'/api/v1/teams.php';
require __DIR__.'/api/v1/posts.php';
