<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Support — {{ $subjectLine }}</title>
</head>
<body style="font-family: -apple-system, sans-serif; color: #1f2226; line-height: 1.5;">
    <h2 style="margin-bottom: 4px;">Nouveau message support</h2>
    <p style="color: #5c5e60; margin-top: 0;">
        Catégorie : <strong>{{ $category }}</strong>
    </p>

    <h3 style="margin-bottom: 4px;">Expéditeur</h3>
    <p style="margin-top: 0;">
        {{ $senderName }} &lt;{{ $senderEmail }}&gt; (ID #{{ $senderId }})
    </p>

    <h3 style="margin-bottom: 4px;">Sujet</h3>
    <p style="margin-top: 0;">{{ $subjectLine }}</p>

    <h3 style="margin-bottom: 4px;">Message</h3>
    <pre style="white-space: pre-wrap; background: #f4f4f4; padding: 12px; border-radius: 8px;">{{ $messageBody }}</pre>
</body>
</html>
