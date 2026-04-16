<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status Update</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;500;700&family=Poppins:wght@500;600;700&display=swap');
        
        body { font-family: 'DM Sans', Tahoma, sans-serif; line-height: 1.6; color: #334155; background-color: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: {{ $isWarning ? '#dc2626' : '#1e3a8a' }}; color: #ffffff; padding: 30px 20px; text-align: center; }
        .header h1 { font-family: 'Poppins', sans-serif; margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 30px 40px; }
        .content h2 { font-family: 'Poppins', sans-serif; color: #0f172a; font-size: 20px; margin-top: 0; }
        
        .status-box {
            background-color: {{ $isWarning ? '#fef2f2' : '#eff6ff' }};
            border-left: 4px solid {{ $isWarning ? '#dc2626' : '#2563eb' }};
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 0 4px 4px 0;
        }
        .status-box p { margin: 5px 0; font-size: 16px; color: {{ $isWarning ? '#991b1b' : '#1e40af' }}; font-weight: 500; }
        
        .button-container { text-align: center; margin: 35px 0; }
        .button { display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-family: 'Poppins', sans-serif; font-weight: 600; }
        .footer { background-color: #f1f5f9; padding: 20px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Application Status Update</h1>
        </div>
        
        <div class="content">
            <h2>Hello {{ $applicantName }},</h2>
            
            <div class="status-box">
                <p><strong>Current Status:</strong> {{ $status }}</p>
            </div>

            <p>{{ $messageText }}</p>

            <div class="button-container">
                <a href="{{ config('app.url') }}" class="button">Visit PCCI Portal</a>
            </div>
            
            <p>Best regards,<br><strong style="font-family: 'Poppins', sans-serif; color: #0f172a;">PCCI Administration</strong></p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Philippine Chamber of Commerce and Industry. All rights reserved.
        </div>
    </div>
</body>
</html>