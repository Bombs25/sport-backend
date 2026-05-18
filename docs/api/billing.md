# Facturation Stripe (Cashier)

Préfixe : `/api/v1/auth`

Routes dans [`routes/api/v1/billing.php`](../../routes/api/v1/billing.php). Toutes exigent **Bearer Sanctum**.

L'abonnement actif débloque les actions équipes protégées par `ensure.subscribed` (voir [teams.md](./teams.md)).

---

## POST /billing/checkout

Crée une session Stripe Checkout (mode abonnement).

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | `auth-billing-write` — 20/min |
| **Corps** | Aucun |

### Réponse 200

```json
{
  "checkout_url": "https://checkout.stripe.com/c/pay/cs_...",
  "session_id": "cs_test_...",
  "trial_days": 7
}
```

`trial_days` est omis si la config vaut 0.

### Erreurs

| Code | Condition |
|------|-----------|
| **409** | Abonnement actif déjà présent |
| **503** | Prix Stripe ou URLs de retour non configurés |

### Notes RN

1. Ouvrir `checkout_url` dans un navigateur in-app (`expo-web-browser`) ou WebView.
2. Après retour (deep link configuré côté Stripe), appeler `GET /billing/subscription`.
3. La synchronisation finale passe par le webhook serveur `POST /stripe/webhook` — **ne pas appeler depuis l'app**.

---

## GET /billing/subscription

État de l'abonnement du compte connecté.

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | `auth-billing-read` — 60/min |

### Réponse 200

Sans abonnement :

```json
{
  "subscribed": false,
  "subscription": null,
  "has_incomplete_payment": false
}
```

Avec abonnement :

```json
{
  "subscribed": true,
  "subscription": {
    "type": "default",
    "stripe_status": "active",
    "stripe_price": "price_...",
    "trial_ends_at": null,
    "ends_at": null,
    "created_at": "2026-05-01T10:00:00+00:00"
  },
  "has_incomplete_payment": false
}
```

Dates au format ISO 8601 ou `null`.

---

## POST /billing/subscription/cancel

Annule l'abonnement (fin de période par défaut).

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | `auth-billing-write` — 20/min |

### Corps

| Champ | Règles |
|-------|--------|
| `immediately` | Optionnel, boolean (défaut `false`) |

- `false` : accès conservé jusqu'à la fin de la période facturée.
- `true` : annulation immédiate.

### Réponse 200

```json
{
  "message": "Abonnement annulé : accès jusqu'à la fin de la période en cours.",
  "subscribed": true,
  "subscription": { },
  "has_incomplete_payment": false
}
```

### Erreurs

| Code | Condition |
|------|-----------|
| **404** | Aucun abonnement |
| **409** | Déjà terminé |
| **409** | Annulation déjà programmée (période de grâce) |

---

## GET /billing/invoices

Liste des factures Stripe du client.

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | `auth-billing-read` — 60/min |

### Query

| Paramètre | Règles |
|-----------|--------|
| `month` | Optionnel, 1–12 ; requis avec `year` |
| `year` | Optionnel, 2000–2100 ; requis avec `month` |
| `limit` | Optionnel, 1–100 (défaut 24) |

### Réponse 200

```json
{
  "invoices": [
    {
      "id": "in_...",
      "number": "ABC-1234",
      "created_at": "2026-05-01T10:00:00+00:00",
      "description": "Abonnement O'Sport",
      "status": "paid",
      "currency": "EUR",
      "total_cents": 999,
      "total": "9,99 €",
      "hosted_invoice_url": "https://invoice.stripe.com/...",
      "invoice_pdf": "https://pay.stripe.com/..."
    }
  ]
}
```

Si l'utilisateur n'a pas de client Stripe : `{ "invoices": [] }` (**200**).

---

## Webhook (hors app mobile)

| Méthode | Chemin | Auth |
|---------|--------|------|
| POST | `/stripe/webhook` | Signature Stripe |

Géré par Laravel Cashier. Ne pas documenter pour l'intégration RN au-delà de : après checkout, attendre quelques secondes puis rafraîchir `GET /billing/subscription`.
