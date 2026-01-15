<!DOCTYPE html>
<html>

<body>
    <p>Dear {{ $member->first_name }},</p>

    <p>
        Thank you for your subscription payment to
        <strong>CSI Centenary Wesley Church</strong>.
    </p>

    <p>
        <strong>Amount Paid:</strong> ₹{{ number_format($amount, 2) }}<br>
        <strong>Financial Year:</strong> {{ $fy }}<br>
        <strong>Months:</strong> {{ implode(', ', array_map('ucfirst', $months)) }}
    </p>

    <p>
        Please find the attached receipt PDF for your records.
    </p>

    <p>
        God bless you,<br>
        <strong>CSI Centenary Wesley Church</strong>
    </p>
</body>

</html>
