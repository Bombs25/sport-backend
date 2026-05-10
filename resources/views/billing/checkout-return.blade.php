<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paiement — retour</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 28rem; margin: 3rem auto; padding: 0 1rem; color: #1a1a1a; }
        code { font-size: 0.85rem; word-break: break-all; }
    </style>
</head>
<body>
    <h1>Retour Checkout</h1>
    <p>Résultat : <strong>{{ $result ?? '—' }}</strong></p>
    @if (! empty($sessionId))
        <p>Session : <code>{{ $sessionId }}</code></p>
    @endif
    <p>En production, cette URL est souvent un <strong>deep link</strong> vers l’app React Native.</p>
</body>
</html>
