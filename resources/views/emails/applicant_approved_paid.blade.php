<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Update</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Poppins:wght@500;600;700&display=swap');
        body { font-family: 'DM Sans', Tahoma, sans-serif; line-height: 1.6; color: #334155; background-color: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #FFFFFF; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: #A50034; color: #FFFFFF; padding: 30px 20px; text-align: center; }
        .header h1 { font-family: 'Poppins', sans-serif; margin: 0; font-size: 24px; font-weight: 600; letter-spacing: 1px; }
        .content { padding: 30px 40px; }
        .content h2 { font-family: 'Poppins', sans-serif; color: #A50034; font-size: 20px; margin-top: 0; margin-bottom: 20px; }
        .greeting { font-size: 16px; font-weight: 500; color: #0f172a; margin-top: 0; }
        .info-box { background-color: #F7C6C7; border-left: 5px solid #D50032; padding: 15px 20px; margin: 25px 0; border-radius: 0 4px 4px 0; color: #A50034; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 25px 0; }
        .footer { background-color: #B22234; color: #FFFFFF; padding: 15px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PCCI VALENZUELA</h1>
        </div>
        <div class="content">
            <h2>Payment Verified</h2>
            <p class="greeting">Hello {{ $applicantName }},</p>
            
            <p>We have successfully received your <strong>application</strong> and <strong>payment</strong>.</p>
            <p>Thank you for completing the required steps. Your submission is now being processed.</p>
            
            <hr class="divider">
            
            <div class="info-box">
                <strong>Next Step:</strong><br>
                Please wait for your login credentials, which will be sent to this email address shortly.
            </div>
            
            <p style="margin-top: 30px;">If you have any questions, feel free to reply to this email.</p>
            <p>Regards,<br><strong style="font-family: 'Poppins', sans-serif; color: #A50034;">PCCI Administration</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} PCCI Valenzuela. All rights reserved.
        </div>
    </div>
</body>
</html>