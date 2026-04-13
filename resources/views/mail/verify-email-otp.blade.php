<x-mail::message>
# Vérification du code

Entrez le code à 6 chiffres envoyé à **{{ $email }}** dans l’application O’Sport (écran « Vérification »).

<div style="text-align: center; margin: 28px 0;">
@foreach(str_split($code) as $digit)
<span style="display: inline-block; min-width: 2.25rem; padding: 10px 12px; margin: 0 4px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 1.35rem; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; letter-spacing: 0.05em;">{{ $digit }}</span>
@endforeach
</div>

Si vous n’avez pas reçu de code, utilisez **Renvoyer le code** dans l’application.

Ce code expire dans 15 minutes. Si vous n’êtes pas à l’origine de cette demande, ignorez ce message.

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
