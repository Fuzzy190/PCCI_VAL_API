<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Renewal Request Submitted</title>
</head>

<body style="font-family: Arial, sans-serif; color: #333;">
    <h1>Renewal Request Submitted</h1>
    <p>Hello Treasurer,</p>
    <p>A renewal request has been submitted and requires your review.</p>
    <table cellpadding="6" cellspacing="0" border="0" style="border-collapse: collapse; width: 100%; max-width: 600px;">
        <tr>
            <th align="left">Business</th>
            <td>{{ $member->applicant->registered_business_name }}</td>
        </tr>
        <tr>
            <th align="left">Amount</th>
            <td>₱{{ number_format($payment->amount, 2) }}</td>
        </tr>
        <tr>
            <th align="left">Due Year</th>
            <td>{{ $due->due_year }}</td>
        </tr>
        <tr>
            <th align="left">Submitted</th>
            <td>{{ $payment->created_at->format('F j, Y H:i') }}</td>
        </tr>
    </table>
    <p>Please review the attached proof of payment and approve or reject this request in the system.</p>
    <p>Thank you,<br>PCCI Membership Team</p>
</body>

</html>