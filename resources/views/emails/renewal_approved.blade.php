<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Renewal Approved</title>
</head>

<body style="font-family: Arial, sans-serif; color: #333;">
    <h1>Membership Renewal Approved</h1>
    <p>Hello {{ $member->applicant->rep_first_name ?? 'Member' }},</p>
    <p>Great news! Your renewal payment has been approved.</p>
    <table cellpadding="6" cellspacing="0" border="0" style="border-collapse: collapse; width: 100%; max-width: 600px;">
        @if($payment)
        <tr>
            <th align="left">Amount</th>
            <td>₱{{ number_format($payment->amount, 2) }}</td>
        </tr>
        @endif
        @if($transaction)
        <tr>
            <th align="left">OR Number</th>
            <td>{{ $transaction->or_number }}</td>
        </tr>
        @endif
        <tr>
            <th align="left">Membership valid until</th>
            <td>{{ $newEndDate->format('F j, Y') }}</td>
        </tr>
    </table>
    <p>Your membership has been extended by one year. Thank you for staying with PCCI.</p>
    <p>Best regards,<br>PCCI Membership Team</p>
</body>

</html>