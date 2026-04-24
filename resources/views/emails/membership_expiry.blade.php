<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Expiry Notification</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Poppins:wght@500;600;700&display=swap');
        body { font-family: 'DM Sans', Tahoma, sans-serif; line-height: 1.6; color: #334155; background-color: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #FFFFFF; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: {{ $isExpired ? '#D50032' : '#A50034' }}; color: #FFFFFF; padding: 30px 20px; text-align: center; }
        .header h1 { font-family: 'Poppins', sans-serif; margin: 0; font-size: 24px; font-weight: 600; letter-spacing: 1px; }
        .content { padding: 30px 40px; }
        .content h2 { font-family: 'Poppins', sans-serif; color: #A50034; font-size: 20px; margin-top: 0; margin-bottom: 20px; }
        .greeting { font-size: 16px; font-weight: 500; color: #0f172a; margin-top: 0; }
        .status-box { background-color: #F7C6C7; border-left: 4px solid {{ $isExpired ? '#D50032' : '#A50034' }}; padding: 15px 20px; margin: 25px 0; border-radius: 0 4px 4px 0; color: #A50034; }
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
            <h2>{{ $isExpired ? 'Membership Expired' : 'Membership Expiry Notice' }}</h2>
            <p class="greeting">Hello, {{ $memberName }},</p>
            
            @if($isExpired)
                <p>We are writing to inform you that your membership with the Philippine Chamber of Commerce and Industry (PCCI) has officially expired.</p>
            @else
                <p>We are writing to remind you that your membership with the Philippine Chamber of Commerce and Industry (PCCI) is expiring soon.</p>
            @endif

            <div class="status-box">
                @if($isExpired)
                    <p><strong>Expired On:</strong> {{ $expiryDate }}</p>
                    <p><strong>Status:</strong> <span style="color: #D50032; font-weight: bold;">Expired</span></p>
                @else
                    <p><strong>Expiration Date:</strong> {{ $expiryDate }}</p>
                    <p><strong>Time Remaining:</strong> {{ $monthsUntil }} Month(s)</p>
                @endif
            </div>
            <p>To continue enjoying the benefits and privileges of your membership, please settle your dues as soon as possible. If you have already made a payment that is currently pending review, please disregard this notice.</p>
            <div class="button-container">
                <a href="{{ config('app.url') }}" class="button">Renew Membership</a>
            </div>
            <p>If you have any questions or require assistance, please do not hesitate to contact our support team.</p>
            <p>Best regards,<br><strong style="font-family: 'Poppins', sans-serif; color: #0f172a;">PCCI Administration</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Philippine Chamber of Commerce and Industry. All rights reserved.
        </div>
    </div>
</body>
</html>