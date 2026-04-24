<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Approved</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Poppins:wght@500;600;700&display=swap');
        body { font-family: 'DM Sans', Tahoma, sans-serif; line-height: 1.6; color: #334155; background-color: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #FFFFFF; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: #A50034; color: #FFFFFF; padding: 30px 20px; text-align: center; }
        .header h1 { font-family: 'Poppins', sans-serif; margin: 0; font-size: 24px; font-weight: 600; }
        .header p { margin: 5px 0 0; font-size: 14px; opacity: 0.9; }
        .content { padding: 30px 40px; }
        .content h2 { font-family: 'Poppins', sans-serif; color: #A50034; font-size: 20px; margin-top: 0; }
        .info-box { background-color: #F7C6C7; border-left: 4px solid #D50032; padding: 15px 20px; margin: 20px 0; border-radius: 0 4px 4px 0; color: #A50034; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 25px 0; }
        .footer { background-color: #f1f5f9; padding: 20px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #F7C6C7; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PCCI Valenzuela</h1>
            <p>Application Notification</p>
        </div>
        <div class="content">
            <h2>Welcome, {{ $applicant->first_name }}!</h2>
            <p>Thank you for your interest in joining <strong>PCCI Valenzuela</strong>. We are pleased to inform you that your application has been <strong style="color: #D50032;">approved</strong>.</p>
            <p>Congratulations on reaching this stage! We are excited to move forward with you.</p>
            <hr class="divider">
            <h3 style="font-family: 'Poppins', sans-serif; color: #A50034; margin-bottom: 10px;">Next Steps</h3>
            <p>Our team will contact you soon with further instructions regarding the onboarding process. Please keep your lines open and monitor your email for updates.</p>
            <div class="info-box">
                <strong>Important:</strong><br>
                Kindly prepare any required documents and ensure your contact details are updated.
            </div>
            <p style="margin-top: 30px;">Thank you for choosing to be part of our community.</p>
            <p>Regards,<br><strong style="font-family: 'Poppins', sans-serif; color: #0f172a;">PCCI Valenzuela</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Philippine Chamber of Commerce and Industry. All rights reserved.
        </div>
    </div>
</body>
</html>