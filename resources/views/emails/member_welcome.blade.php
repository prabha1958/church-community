<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Welcome to Our Community</title>
</head>

<body>
    <p>Dear {{ $member->family_name ?? ($member->first_name ?? 'Member') }},</p>

    <p>Welcome and thank you for registering as a member. Below are your membership details:</p>

    <ul>
        <li><strong>Member ID:</strong> {{ $member->id }}</li>
        <li><strong>Email:</strong> {{ $member->email ?? 'N/A' }}</li>
        <li><strong>Mobile Number:</strong> {{ $member->mobile_number ?? 'N/A' }}</li>
    </ul>

    <p>You can login using your email via the <strong>Email OTP</strong> option (one-time code will be sent to your
        email when you choose that flow).</p>

    <p>If you did not sign up or have questions, please contact us.</p>

    <p>Blessings,<br>Your Organization</p>
</body>

</html>
