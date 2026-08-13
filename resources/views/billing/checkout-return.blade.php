@php
    /** @var string $result */
    /** @var string $deepLink */
    $isSuccess = ($result ?? null) === 'success';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Repli si le JS est bloqué : rebond immédiat vers le deep link de l'app. --}}
    <meta http-equiv="refresh" content="0;url={{ $deepLink }}">
    <title>Retour à O'Sport…</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 26rem; margin: 0 auto; padding: 4rem 1.25rem; color: #1a1a1a; text-align: center; }
        .badge { width: 64px; height: 64px; border-radius: 50%; margin: 0 auto 1.25rem; display: flex; align-items: center; justify-content: center; font-size: 30px; }
        .ok { background: #e8f0fe; color: #376CD5; }
        .ko { background: #f1f3f5; color: #6b7280; }
        h1 { font-size: 1.25rem; margin: 0 0 .5rem; }
        p { color: #6b7280; margin: 0 0 1.5rem; }
        a.btn { display: inline-block; background: #376CD5; color: #fff; text-decoration: none; font-weight: 600; padding: .85rem 1.5rem; border-radius: 999px; }
    </style>
</head>
<body>
    <div class="badge {{ $isSuccess ? 'ok' : 'ko' }}">{{ $isSuccess ? '✓' : '↩' }}</div>
    <h1>{{ $isSuccess ? 'Paiement validé' : 'Paiement non finalisé' }}</h1>
    <p>Redirection vers l'application O'Sport…</p>
    <a class="btn" href="{{ $deepLink }}">Revenir à l'application</a>

    <script>
        // Rebond automatique vers le deep link de l'app (ex. osport://billing/success).
        // openAuthSessionAsync côté RN intercepte ce schéma et ferme le navigateur intégré.
        window.location.replace(@json($deepLink));
    </script>
</body>
</html>
