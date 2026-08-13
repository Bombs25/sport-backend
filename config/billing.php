<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Type d'abonnement Cashier (colonne `subscriptions.type`)
    |--------------------------------------------------------------------------
    */
    'subscription_type' => env('BILLING_SUBSCRIPTION_TYPE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Prix Stripe récurrent (mensuel)
    |--------------------------------------------------------------------------
    |
    | Obligatoire : identifiant `price_...` du prix **récurrent** lié au produit
    | (le `prod_...` seul ne suffit pas pour souscrire).
    |
    */
    'subscription_price_id' => env('STRIPE_SUBSCRIPTION_PRICE_ID'),

    /*
    |--------------------------------------------------------------------------
    | Essai gratuit (jours) — Checkout + abonnement Cashier
    |--------------------------------------------------------------------------
    |
    | Nombre de jours d’essai après souscription (0 = pas d’essai).
    | Avec Stripe Checkout, Cashier impose un minimum d’environ 48 h si la valeur
    | est trop faible (contrainte session 24 h + marge).
    |
    */
    'trial_days' => (int) env('BILLING_SUBSCRIPTION_TRIAL_DAYS', 0),

    /*
    |--------------------------------------------------------------------------
    | Produit Stripe (référence / analytics uniquement)
    |--------------------------------------------------------------------------
    */
    'product_id' => env('STRIPE_PRODUCT_ID'),

    /*
    |--------------------------------------------------------------------------
    | URLs de retour Checkout (React Native / WebView)
    |--------------------------------------------------------------------------
    |
    | Stripe remplace {CHECKOUT_SESSION_ID} dans success_url.
    | Ex. app profonde : myapp://billing/success?session_id={CHECKOUT_SESSION_ID}
    |
    */
    'checkout_success_url' => env('BILLING_CHECKOUT_SUCCESS_URL'),

    'checkout_cancel_url' => env('BILLING_CHECKOUT_CANCEL_URL'),

    /*
    |--------------------------------------------------------------------------
    | Schéma d'app pour le retour deep link (React Native)
    |--------------------------------------------------------------------------
    |
    | Stripe n'accepte pas un `success_url` en schéma custom : le Checkout revient
    | d'abord sur la page web `billing/checkout/return`, qui **rebondit** ensuite vers
    | `<scheme>://billing/success` ou `<scheme>://billing/cancel` pour rouvrir l'app.
    | Doit correspondre au `scheme` de l'app Expo (osport-app/app.json).
    |
    */
    'app_return_scheme' => env('BILLING_APP_RETURN_SCHEME', 'osport'),

];
