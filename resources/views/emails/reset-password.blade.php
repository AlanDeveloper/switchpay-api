<!DOCTYPE html>
<html>
<body>
    <div class="container">
        <div class="header">{{ config('app.name') }}</div>
        <p>Hello,</p>
        <p>We received a request to reset your password. Click the button below to choose a new one:</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.url') . '/reset-password?token=' . $token . '&email=' . $email }}"
               style="background-color: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;">
                Reset Password
            </a>
        </div>
        <p>This link will expire in <strong>60 minutes</strong>.</p>
        <p>If you did not request a password reset, please ignore this email — your password will remain unchanged.</p>
        <div class="footer">
            Regards,<br>
            The {{ config('app.name') }} Team
        </div>
    </div>
</body>
</html>
