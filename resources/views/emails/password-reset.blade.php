<!DOCTYPE html>
<html>
<body>
    <h2>Hello {{ $userName }},</h2>

    <p>You requested to reset your password.</p>

    <p>
        Click the link below to reset it (valid for {{ $expiresIn }}):
    </p>

    <p>
        <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
    </p>

    <p>If you did not request this reset, please ignore this email.</p>

    <br>
    <p>Regards,<br>BarberSaaS Team</p>
</body>
</html>
