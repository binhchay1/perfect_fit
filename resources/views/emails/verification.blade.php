<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Verify Your Account</title>
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
            background-color: #28a745;
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
        <h1>Welcome to Perfect Fit!</h1>
    </div>

    <div class="content">
        <p>Hello {{ $name }},</p>

        <p>Thank you for registering with Perfect Fit. To complete your registration, please verify your email address
            by clicking the button below:</p>

        <a href="{{ $verificationUrl }}" class="button">Verify Email Address</a>

        <p>If you did not create an account, please ignore this email.</p>

        <p>This verification link will expire in 24 hours.</p>
    </div>

    <div class="footer">
        <p>If you're having trouble clicking the "Verify Email Address" button, copy and paste the URL below into your
            web browser:</p>
        <p>{{ $verificationUrl }}</p>
    </div>
</body>

</html>
