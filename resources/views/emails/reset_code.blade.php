<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Code</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Password Reset Request</h2>
    <p>Hello,</p>
    <p>You are receiving this email because we received a password reset request for your account.</p>
    <p>Your 6-digit password reset code is:</p>
    <div style="background: #f4f4f4; padding: 10px; font-size: 24px; font-weight: bold; letter-spacing: 5px; text-align: center; border-radius: 5px; display: inline-block;">
        {{ $code }}
    </div>
    <p>This code will expire in 60 minutes.</p>
    <p>If you did not request a password reset, no further action is required.</p>
    <p>Regards,<br>School Management System</p>
</body>
</html>
