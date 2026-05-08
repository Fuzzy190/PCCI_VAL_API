<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Renewal Request Rejected</title>
</head>

<body style="font-family: Arial, sans-serif; color: #333;">
    <h1>Renewal Request Rejected</h1>
    <p>Hello {{ $member->applicant->rep_first_name ?? 'Member' }},</p>
    <p>We reviewed your renewal submission and unfortunately it was rejected.</p>
    <p><strong>Reason:</strong> {{ $reason }}</p>
    @if($payment)
    <p><strong>Amount Submitted:</strong> ₱{{ number_format($payment->amount, 2) }}</p>
    @endif
    <p>Please resubmit your payment proof after addressing the issue.</p>
    <p>Thank you,<br>PCCI Membership Team</p>
</body>

</html>