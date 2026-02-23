<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Sistem PKL</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937;">
    <p>Halo {{ $name }},</p>

    <p>Akun Anda untuk Sistem PKL sudah dibuat oleh admin. Berikut informasi login Anda:</p>

    <ul>
        <li><strong>Email:</strong> {{ $email }}</li>
        <li><strong>Password:</strong> {{ $password }}</li>
    </ul>

    <p>Silakan login ke sistem menggunakan Email dan Password yang telah disediakan.</p>

    <p>Terima kasih.</p>
</body>

</html>
