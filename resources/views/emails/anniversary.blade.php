<!doctype html>
<html>

<head>
    <meta charset="utf-8">
</head>

<body>
    <p>Dear {{ $member->first_name ?? ($member->last_name ?? 'Friend') }},</p>

    <p>Warm wishes on your wedding anniversary! May God bless your matrimonial journey with Mrs.
        {{ $member->spouse_name }}. 🎉 for all the years come.</p>

    <p>With blessings,<br />Your Church / Community</p>
</body>

</html>
