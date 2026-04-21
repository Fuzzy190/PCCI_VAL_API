<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Application Approved</title>
</head>
<body style="margin: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">

    <!-- Container -->
    <div style="max-width: 700px; margin: auto; background-color: #ffffff;">

        <!-- Header -->
        <div style="background-color: #b3202a; color: #ffffff; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">PCCI Valenzuela</h1>
            <p style="margin: 5px 0 0;">Application Notification</p>
        </div>

        <!-- Body -->
        <div style="padding: 30px; color: #333333;">

            <h2 style="margin-top: 0;">Welcome, {{ $applicant->first_name }}!</h2>

            <p>
                Thank you for your interest in joining <strong>PCCI Valenzuela</strong>.
                We are pleased to inform you that your application has been 
                <strong style="color: green;">approved</strong>.
            </p>

            <p>
                Congratulations on reaching this stage! We are excited to move forward with you.
            </p>

            <!-- Divider -->
            <hr style="border: none; border-top: 1px solid #dddddd; margin: 25px 0;">

            <!-- Next Steps -->
            <h3 style="margin-bottom: 10px;">Next Steps</h3>

            <p>
                Our team will contact you soon with further instructions regarding the onboarding process.
                Please keep your lines open and monitor your email for updates.
            </p>

            <!-- Highlight Box -->
            <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #b3202a; margin-top: 20px;">
                <strong>Important:</strong><br>
                Kindly prepare any required documents and ensure your contact details are updated.
            </div>

            <!-- Closing -->
            <p style="margin-top: 30px;">
                Thank you for choosing to be part of our community.
            </p>

            <p>
                Regards,<br>
                <strong>PCCI Valenzuela</strong>
            </p>

        </div>

    </div>

</body>
</html>