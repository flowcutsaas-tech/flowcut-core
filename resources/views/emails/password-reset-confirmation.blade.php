<!DOCTYPE html>
<html>
<body>
    <h2>Hello {{ $userName }},</h2>

    <p>Your password has been successfully reset.</p>

    <p>You can now log in using the link below:</p>

    <p>
        <a href="{{ $loginUrl }}">Login</a>
    </p>

    <p>If you did not perform this action, contact support immediately at {{ $supportEmail }}.</p>

    <br>
    <p>Regards,<br>BarberSaaS Team</p>
</body>
</html>
