<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
        }

        .content {
            padding: 20px;
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-top: 20px;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }

        .footer {
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Reset Password</h1>
    </div>

    <div class="content">
        <p>Hello,</p>

        <p>You are receiving this email because we received a password reset request for your account.</p>

        <p>Please click the button below to reset your password:</p>

        <a href="{{ url('/reset-password/' . $token) }}" class="button">Reset Password</a>

        <p>If you did not request a password reset, no further action is required.</p>

        <p>This password reset link will expire in 15 minutes.</p>
    </div>

    <div class="footer">
        <p>If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web
            browser:</p>
        <p>{{ url('/reset-password/' . $token) }}</p>
    </div>
</body>

</html>
