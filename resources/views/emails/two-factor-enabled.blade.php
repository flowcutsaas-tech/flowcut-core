<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Two-Factor Authentication Enabled</title>
</head>
<body>
    <h2>Hello {{ $userName }},</h2>

    <p>Your Two-Factor Authentication (2FA) has been successfully enabled.</p>

    <p>Here are your backup codes:</p>

    <ul>
        @foreach ($backupCodes as $code)
            <li>{{ $code }}</li>
        @endforeach
    </ul>

    <p>Keep these codes in a safe place. They can be used if you lose access to your authenticator app.</p>

    <p>Regards,<br>BarberSaaS</p>
</body>
</html>
