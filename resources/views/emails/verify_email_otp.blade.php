<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - PCCI</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Poppins:wght@500;600;700&display=swap');
        
        body { font-family: 'DM Sans', Tahoma, sans-serif; line-height: 1.6; color: #334155; background-color: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #FFFFFF; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: #A50034; color: #FFFFFF; padding: 30px 20px; text-align: center; }
        .header h1 { font-family: 'Poppins', sans-serif; margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 30px 40px; text-align: center; }
        .content h2 { font-family: 'Poppins', sans-serif; color: #0f172a; font-size: 20px; margin-top: 0; }
        
        .otp-box {
            background-color: #F7C6C7;
            border: 2px dashed #D50032;
            color: #A50034;
            font-family: 'Poppins', sans-serif;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 4px;
            padding: 20px;
            margin: 30px auto;
            border-radius: 8px;
            max-width: 250px;
        }
        
        .warning-text { font-size: 13px; color: #64748b; margin-top: 15px; }
        .footer { background-color: #f1f5f9; padding: 20px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PCCI Valenzuela</h1>
        </div>
        
        <div class="content">
            <h2>Email Verification</h2>
            <p>Hello,</p>
            <p>Please use the verification code below to verify your email address and complete your account setup.</p>

            <div class="otp-box">
                {{ $otp }}
            </div>

            <p class="warning-text">This code will expire in 10 minutes. If you did not request this, please safely ignore this email.</p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Philippine Chamber of Commerce and Industry. All rights reserved.
        </div>
    </div>
</body>
</html>