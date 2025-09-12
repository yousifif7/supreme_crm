<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset Code</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .btn-code {
            display: inline-block;
            padding: 10px 20px;
            font-size: 1.2rem;
            color: #fff !important;
            background-color: #0d6efd;
            border-radius: 5px;
            text-decoration: none;
        }
        .footer-text {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <h2 class="text-center mb-4">SUPREME CRM</h2>

        <p>Hello ,</p>
        <p>You requested to reset your password. Please use the following code to proceed:</p>

        <p class="text-center">
            <b class="btn-code">{{ $code }}</b>
        </p>

        <p>This code will expire in <strong>15 minutes</strong>.</p>
        <p>If you did not request a password reset, please ignore this email.</p>

        <p class="footer-text text-center">© {{ date('Y') }} SUPREME CRM. All rights reserved.</p>
    </div>
</body>
</html>
