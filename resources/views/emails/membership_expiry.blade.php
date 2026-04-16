<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Expiry Notification</title>
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
            background-color: 
            {{ $isExpired ? '#e53e3e' : '#f59e0b' }};
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
        .status-box {
            background-color: #f8fafc;
            border-left: 4px solid {{ $isExpired ? '#e53e3e' : '#f59e0b' }};
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 0 4px 4px 0;
        }
        .status-box p {
            margin: 5px 0;
            font-size: 16px;
        }
        .status-box strong {
            color: #1a202c;
        }
        .button-container {
            text-align: center;
            margin: 35px 0;
        }
        .button {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
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
            <h1>{{ $isExpired ? 'Membership Expired' : 'Membership Expiry Notice' }}</h1>
        </div>
        
        <div class="content">
            <h2>Hello, {{ $memberName }},</h2>
            
            @if($isExpired)
                <p>We are writing to inform you that your membership with the Philippine Chamber of Commerce and Industry (PCCI) has officially expired.</p>
            @else
                <p>We are writing to remind you that your membership with the Philippine Chamber of Commerce and Industry (PCCI) is expiring soon.</p>
            @endif

            <div class="status-box">
                @if($isExpired)
                    <p><strong>Expired On:</strong> {{ $expiryDate }}</p>
                    <p><strong>Status:</strong> Expired</p>
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
            
            <p>Best regards,<br><strong>PCCI Administration</strong></p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Philippine Chamber of Commerce and Industry. All rights reserved.
        </div>
    </div>
</body>
</html>