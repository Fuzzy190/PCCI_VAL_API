<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Changed Successfully</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Poppins:wght@500;600;700&display=swap');
        body { font-family: 'DM Sans', Tahoma, sans-serif; line-height: 1.6; color: #334155; background-color: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #FFFFFF; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: #A50034; color: #FFFFFF; padding: 30px 20px; text-align: center; }
        .header h1 { font-family: 'Poppins', sans-serif; margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 30px 40px; }
        .content h2 { font-family: 'Poppins', sans-serif; color: #A50034; font-size: 20px; margin-top: 0; }
        .alert-box { background-color: #F7C6C7; border-left: 4px solid #A50034; padding: 15px 20px; margin: 25px 0; border-radius: 0 4px 4px 0; }
        .alert-box p { margin: 0; font-size: 15px; color: #A50034; font-weight: 500; }
        .warning-text { background-color: #ffffff; border: 1px solid #D50032; padding: 15px; margin-top: 20px; border-radius: 6px; font-size: 14px; color: #D50032; }
        .button-container { text-align: center; margin: 35px 0; }
        .button { display: inline-block; background-color: #D50032; color: #FFFFFF; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-family: 'Poppins', sans-serif; font-weight: 600; }
        .footer { background-color: #f1f5f9; padding: 20px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #F7C6C7; }
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
                <strong style="color: #A50034;">Didn't make this change?</strong><br>
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