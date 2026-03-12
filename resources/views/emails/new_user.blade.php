<!DOCTYPE html>
<html>
<body>
    <div class="container">
        <div class="header">Welcome to {{ config('app.name') }}!</div>

        <p>Hello,</p>
        <p>Your account has been successfully created. You can now log in using the credentials below:</p>

        <div class="credentials">
            <strong>Email:</strong> {{ $user->email }} <br>
            <strong>Temporary Password:</strong> <code>{{ $tempPassword }}</code>
        </div>

        <p class="alert">
            Important: For security reasons, we strongly recommend that you change your password
            to a personal one immediately after your first login.
        </p>

        <p>If you did not request this account, please ignore this email.</p>

        <div class="footer">
            Regards,<br>
            The {{ config('app.name') }} Team
        </div>
    </div>
</body>
</html>
