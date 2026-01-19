<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Alliance Payment Receipt</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .box {
            border: 1px solid #000;
            padding: 10px;
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        td {
            padding: 6px;
            vertical-align: top;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2>CSI Centenary Wesley Church</h2>
        <h3>Alliance Payment Receipt</h3>
    </div>

    <div class="box">
        <table>
            <tr>
                <td><strong>Receipt No:</strong></td>
                <td>{{ $receipt_no }}</td>
                <td><strong>Date:</strong></td>
                <td>{{ $date }}</td>
            </tr>
            <tr>
                <td><strong>Member Name:</strong></td>
                <td colspan="3">{{ $member->first_name }} {{ $member->last_name }}</td>
            </tr>
            <tr>
                <td><strong>Alliance Name:</strong></td>
                <td colspan="3">{{ $alliance->first_name }} {{ $alliance->last_name }}</td>
            </tr>
            <tr>
                <td><strong>Amount Paid:</strong></td>
                <td colspan="3">₹ {{ number_format($amount, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Payment ID:</strong></td>
                <td colspan="3">{{ $payment_id }}</td>
            </tr>
        </table>
    </div>

    <p style="margin-top:20px;">
        This is a system-generated receipt. No signature required.
    </p>

</body>

</html>
