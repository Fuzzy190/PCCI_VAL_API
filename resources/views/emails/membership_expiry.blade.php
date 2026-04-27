<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Renewal Reminder</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Poppins:wght@500;600;700&display=swap');
        body { font-family: 'DM Sans', Tahoma, sans-serif; line-height: 1.6; color: #334155; background-color: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #FFFFFF; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: #A50034; color: #FFFFFF; padding: 30px 20px; text-align: center; }
        .header h1 { font-family: 'Poppins', sans-serif; margin: 0; font-size: 24px; font-weight: 600; letter-spacing: 1px; }
        .content { padding: 30px 40px; }
        .content h2 { font-family: 'Poppins', sans-serif; color: #A50034; font-size: 20px; margin-top: 0; margin-bottom: 20px; }
        .greeting { font-size: 16px; font-weight: 500; color: #0f172a; margin-top: 0; }
        .status-box { background-color: #F7C6C7; border-left: 4px solid #A50034; padding: 15px 20px; margin: 25px 0; border-radius: 0 4px 4px 0; color: #A50034; }
        .status-box p { margin: 5px 0; font-size: 16px; }
        .button-container { text-align: center; margin: 35px 0; }
        .button { display: inline-block; background-color: #D50032; color: #FFFFFF; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-family: 'Poppins', sans-serif; font-weight: 600; }
        .footer { background-color: #f1f5f9; padding: 20px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #F7C6C7; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PCCI VALENZUELA</h1>
        </div>
        <div class="content">
            <h2>Membership Renewal Reminder</h2>
            <p class="greeting">Hello, {{ $memberName }},</p>
            
            <p>We hope you are doing well.</p>
            
            <p>This is a friendly reminder that it is now time to renew your membership with the Philippine Chamber of Commerce and Industry, PCCI Valenzuela.</p>
            
            <p>Your membership renewal helps us continue building a stronger and more connected business community. We truly value your presence, participation, and contribution to PCCI Valenzuela, and we would love to continually have you as part of our community.</p>

            <p>By renewing your membership, you can continue receiving updates, support, access to upcoming events, business networking opportunities, and other membership benefits.</p>

            <div class="status-box">
                <p><strong>Membership Renewal Date:</strong> {{ $expiryDate }}</p>
                <p><strong>Status:</strong> <span style="color: #D50032; font-weight: bold;">For Renewal</span></p>
            </div>
            
            <p>To continue enjoying the benefits and privileges of your membership, kindly complete your annual renewal and settle your membership dues at your earliest convenience.</p>
            
            <div class="button-container">
                <a href="{{ config('app.url') }}" class="button">Renew Membership</a>
            </div>
            
            <p>If you have already made a payment and it is currently pending review, please disregard this reminder.</p>
            <p>For any questions or assistance, feel free to contact our support team.</p>
            
            <p>Best regards,<br><strong style="font-family: 'Poppins', sans-serif; color: #0f172a;">PCCI Valenzuela Administration</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Philippine Chamber of Commerce and Industry. All rights reserved.
        </div>
    </div>
</body>
</html>