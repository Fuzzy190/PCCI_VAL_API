<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Changed Successfully</title>
    <style>
        /* Import Poppins and DM Sans from Google Fonts */
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;500;700&family=Poppins:wght@500;600;700&display=swap');
        
        body {
            font-family: 'DM Sans', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #334155;
            background-color: #f8fafc;
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
            background-color: #1e3a8a; /* Deep Navy Blue */
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 30px 40px;
        }
        .content h2 {
            font-family: 'Poppins', sans-serif;
            color: #0f172a;
            font-size: 20px;
            margin-top: 0;
        }
        .alert-box {
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 0 4px 4px 0;
        }
        .alert-box p {
            margin: 0;
            font-size: 15px;
            color: #1e40af;
            font-weight: 500;
        }
        .warning-text {
            font-size: 14px;
            color: #ef4444; /* Warning Red */
            margin-top: 20px;
            font-weight: 500;
            padding: 10px;
            background-color: #fef2f2;
            border-radius: 6px;
        }
        .button-container {
            text-align: center;
            margin: 35px 0;
        }
        .button {
            display: inline-block;
            background-color: #2563eb; /* Primary Blue Accent */
            color: #ffffff;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 16px;
        }
        .footer {
            background-color: #f1f5f9;
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
            <h1>Security Update</h1>
        </div>
        
        <div class="content">
            <h2>Hello {{ $notifiable->name ?? 'Member' }},</h2>
            
            <p>This is a confirmation that the password for your Philippine Chamber of Commerce and Industry (PCCI) account was recently changed.</p>

            <div class="alert-box">
                <p>For your security, you have been logged out from all active devices.</p>
            </div>

            <p>Please log in again using your new credentials to continue accessing your account.</p>

            <div class="button-container">
                <a href="{{ config('app.url') }}/login" class="button">Log In to Your Account</a>
            </div>

            <div class="warning-text">
                <strong>Didn't make this change?</strong><br>
                If you did not perform this action, please contact our support team immediately to secure your account.
            </div>
            
            <p style="margin-top: 30px;">Best regards,<br><strong style="font-family: 'Poppins', sans-serif; color: #0f172a;">PCCI Administration</strong></p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Philippine Chamber of Commerce and Industry. All rights reserved.
        </div>
    </div>
</body>
</html>