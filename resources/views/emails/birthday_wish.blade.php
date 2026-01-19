<!doctype html>
<html>

<head>
    <meta charset="utf-8">
</head>

<body>
    <p>Dear {{ $member->first_name ?? ($member->name ?? 'Friend') }},</p>

    <p>Warm wishes on your birthday! May God bless you abundantly. 🎉</p>

    <p>With blessings,<br />Your Church / Community</p>
</body>

</html>
