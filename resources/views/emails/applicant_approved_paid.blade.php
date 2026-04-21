<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Application Update</title>
</head>
<body style="margin: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">

    <!-- Container -->
    <div style="max-width: 700px; margin: auto; background-color: #FFFFFF;">

        <!-- Header -->
        <div style="background-color: #A50034; color: #FFFFFF; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">PCCI – Valenzuela</h1>
            <p style="margin: 5px 0 0;">Philippine Chamber of Commerce and Industry</p>
        </div>

        <!-- Body -->
        <div style="padding: 30px; color: #333333;">

            <h2 style="margin-top: 0;">Hello {{ $applicant->first_name }},</h2>

            <p>
                We have successfully received your <strong>application</strong> and 
                <strong>payment</strong>.
            </p>

            <p>
                Thank you for completing the required steps. Your submission is now being processed.
            </p>

            <!-- Divider -->
            <hr style="border: none; border-top: 1px solid #dddddd; margin: 25px 0;">

            <!-- Info Box -->
            <div style="background-color: #F7C6C7; padding: 15px; border-left: 5px solid #D50032;">
                <strong>Next Step:</strong><br>
                Please wait for your login credentials, which will be sent to this email address.
            </div>

            <!-- Closing -->
            <p style="margin-top: 30px;">
                If you have any questions, feel free to reply to this email.
            </p>

            <p>
                Regards,<br>
                <strong style="color: #B22234;">PCCI Valenzuela</strong>
            </p>

        </div>

        <!-- Footer -->
        <div style="background-color: #B22234; color: #FFFFFF; text-align: center; padding: 10px; font-size: 12px;">
            © {{ date('Y') }} PCCI Valenzuela. All rights reserved.
        </div>

    </div>

</body>
</html>