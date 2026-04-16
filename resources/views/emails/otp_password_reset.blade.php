<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .header {
            background-color: #2563eb; /* Professional Blue */
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px 40px;
        }
        .content h2 {
            color: #2d3748;
            font-size: 20px;
            margin-top: 0;
        }
        .otp-box {
            background-color: #f8fafc;
            border: 2px dashed #2563eb;
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
            text-align: center;
        }
        .otp-code {
            font-size: 32px;
            font-weight: bold;
            color: #1a202c;
            letter-spacing: 5px;
            margin: 0;
        }
        .warning-text {
            font-size: 14px;
            color: #64748b;
            margin-top: 10px;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Request</h1>
        </div>
        
        <div class="content">
            <h2>Hello {{ $notifiable->name ?? 'Member' }},</h2>
            
            <p>We received a request to reset the password for your Philippine Chamber of Commerce and Industry (PCCI) account.</p>

            <p>Please use the following One-Time Password (OTP) to complete your password reset process:</p>

            <div class="otp-box">
                <p class="otp-code">{{ $otp }}</p>
                <p class="warning-text">This code will expire in 10 minutes.</p>
            </div>

            <p>If you did not request a password reset, no further action is required. Please ignore this email or contact support if you have concerns.</p>
            
            <p>Best regards,<br><strong>PCCI Administration</strong></p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Philippine Chamber of Commerce and Industry. All rights reserved.
        </div>
    </div>
</body>
</html>