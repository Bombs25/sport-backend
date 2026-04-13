<x-mail::message>
# Code de réinitialisation

Vous avez demandé à réinitialiser le mot de passe de votre compte O’Sport associé à **{{ $email }}**.

Saisissez ce code à **6 chiffres** à l’étape suivante de l’application (écran « Nouveau mot de passe »), puis choisissez un mot de passe **différent de l’ancien**, avec **au moins 8 caractères**, des **lettres**, des **chiffres** et des **symboles**.

<div style="text-align: center; margin: 28px 0;">
@foreach(str_split($code) as $digit)
<span style="display: inline-block; min-width: 2.25rem; padding: 10px 12px; margin: 0 4px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 1.35rem; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; letter-spacing: 0.05em;">{{ $digit }}</span>
@endforeach
</div>

Ce code expire dans **15 minutes**. Si vous n’êtes pas à l’origine de cette demande, ignorez ce message : votre mot de passe actuel reste inchangé.

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
