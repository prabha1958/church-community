<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
        }

        .box {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
        }

        .right {
            text-align: right;
        }

        .footer {
            margin-top: 30px;
            font-size: 11px;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="title">CSI Centenary Wesley Church</div>
        <div>Subscription Payment Receipt</div>
    </div>

    <div class="box">
        <strong>Receipt No:</strong> {{ $receipt_no }}<br>
        <strong>Date:</strong> {{ $date }}<br>
        <strong>Financial Year:</strong> {{ $financial_year }}
    </div>

    <div class="box">
        <strong>Member Name:</strong> {{ $member->first_name }} {{ $member->last_name }}<br>
        <strong>Member ID:</strong> {{ $member->id }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($months as $m)
                <tr>
                    <td>{{ ucfirst($m) }}</td>
                    <td>Paid</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <br>

    <table>
        <tr>
            <th>Total Amount</th>
            <th class="right">₹ {{ number_format($amount, 2) }}</th>
        </tr>
    </table>

    <br>

    <div class="box">
        <strong>Razorpay Order ID:</strong> {{ $razorpay_order_id }}<br>
        <strong>Razorpay Payment ID:</strong> {{ $razorpay_payment_id }}
    </div>

    <div class="footer">
        This is a system-generated receipt. No signature required.
    </div>

</body>

</html>
