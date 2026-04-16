<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to PCCI</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;500;700&family=Poppins:wght@500;600;700&display=swap');
        
        body { font-family: 'DM Sans', Tahoma, sans-serif; line-height: 1.6; color: #334155; background-color: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: #1e3a8a; color: #ffffff; padding: 30px 20px; text-align: center; }
        .header h1 { font-family: 'Poppins', sans-serif; margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 30px 40px; }
        .content h2 { font-family: 'Poppins', sans-serif; color: #0f172a; font-size: 20px; margin-top: 0; }
        
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #2563eb;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box h3 {
            font-family: 'Poppins', sans-serif;
            color: #1e3a8a;
            margin-top: 0;
            font-size: 16px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }
        .info-box p { margin: 6px 0; font-size: 15px; color: #1a202c; }
        .info-box strong { color: #475569; font-weight: 600; display: inline-block; width: 140px; }
        
        .credentials-box {
            background-color: #eff6ff;
            border: 2px dashed #2563eb;
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
        }
        .credentials-box p { margin: 8px 0; font-size: 16px; color: #1a202c; }
        .credentials-box strong { color: #1e3a8a; font-family: 'Poppins', sans-serif; }
        
        .warning-text { font-size: 13px; color: #ef4444; margin-top: 15px; font-weight: 500; }
        .button-container { text-align: center; margin: 35px 0; }
        .button { display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-family: 'Poppins', sans-serif; font-weight: 600; }
        .footer { background-color: #f1f5f9; padding: 20px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to PCCI!</h1>
        </div>
        
        <div class="content">
            <h2>Hello {{ $applicantName }},</h2>
            
            <p>Congratulations! Your payment has been completely processed, and your official Philippine Chamber of Commerce and Industry (PCCI) member account is now active.</p>

            <div class="info-box">
                <h3>Official Receipt Details</h3>
                <p><strong>OR Number:</strong> {{ $orNumber }}</p>
                <p><strong>Amount Paid:</strong> ₱{{ $amount }}</p>
                <p><strong>Payment Date:</strong> {{ $paymentDate }}</p>
                <p><strong>Received By:</strong> {{ $receivedBy }}</p>
            </div>

            <div class="info-box">
                <h3>Membership Details</h3>
                <p><strong>Category:</strong> {{ $membershipType }}</p>
                <p><strong>Induction Date:</strong> {{ $inductionDate }}</p>
                <p><strong>Valid Until:</strong> {{ $expiryDate }}</p>
            </div>

            <div class="credentials-box">
                <p style="margin-top: 0; margin-bottom: 15px; font-weight: 600; color: #1e3a8a;">Your Portal Login Credentials:</p>
                <p><strong>Email Address:</strong> {{ $email }}</p>
                <p><strong>Password:</strong> {{ $password }}</p>
                <p class="warning-text">For security reasons, we strongly recommend changing this password immediately after your first login.</p>
            </div>

            <div class="button-container">
                <a href="{{ config('app.url') }}/login" class="button">Log In to Your Account</a>
            </div>
            
            <p>Best regards,<br><strong style="font-family: 'Poppins', sans-serif; color: #0f172a;">PCCI Administration</strong></p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Philippine Chamber of Commerce and Industry. All rights reserved.
        </div>
    </div>
</body>
</html>