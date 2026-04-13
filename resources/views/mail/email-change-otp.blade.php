<x-mail::message>
# Confirmer votre nouvel e-mail

Vous avez demandé à remplacer l’adresse de votre compte O’Sport par **{{ $email }}**.

Saisissez ce code à **6 chiffres** dans l’application pour confirmer le changement :

<div style="text-align: center; margin: 28px 0;">
@foreach(str_split($code) as $digit)
<span style="display: inline-block; min-width: 2.25rem; padding: 10px 12px; margin: 0 4px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 1.35rem; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; letter-spacing: 0.05em;">{{ $digit }}</span>
@endforeach
</div>

Ce code expire dans **15 minutes**. Si vous n’êtes pas à l’origine de cette demande, ignorez ce message.

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
