<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Subscription Receipt</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 14px;
            color: #000;
            margin: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
        }

        .header h2 {
            margin: 0;
            font-size: 20px;
        }

        .header p {
            margin: 4px 0;
            font-size: 13px;
        }

        .receipt-box {
            width: 100%;
            border: 1px solid #333;
            border-collapse: collapse;
        }

        .receipt-box td {
            padding: 10px;
            border: 1px solid #333;
        }

        .label {
            width: 35%;
            font-weight: bold;
            background-color: #f5f5f5;
        }

        .value {
            width: 65%;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #555;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2>Subscription Receipt</h2>
        <p>CSI Centenary Wesley Church, Ramkote</p>
        <p>Hyderabad</p>
    </div>

    <table class="receipt-box">
        <tr>
            <td class="label">Member Name</td>
            <td class="value">{{ $member->first_name ?? '-' }} {{ $member->last_name ?? '-' }}</td>
        </tr>

        <tr>
            <td class="label">Member ID</td>
            <td class="value">{{ $member->id ?? '-' }}</td>
        </tr>

        <tr>
            <td class="label">Financial Year</td>
            <td class="value">{{ $financial_year ?? '-' }}</td>
        </tr>

        <tr>
            <td class="label">Amount Paid</td>
            <td class="value">₹ {{ number_format($amount ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td class="label">For months</td>
            <td class="value"> {{ implode(', ', array_map('ucfirst', $months)) }}</td>
        </tr>

        <tr>
            <td class="label">Payment ID</td>
            <td class="value">{{ $receipt_no ?? '-' }}</td>
        </tr>

        <tr>
            <td class="label">Payment Date</td>
            <td class="value">
                {{ isset($date) ? \Carbon\Carbon::parse($date)->format('d M Y') : '-' }}
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>This is a system generated receipt.</p>
    </div>

</body>

</html>
