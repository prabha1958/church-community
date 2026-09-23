<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Church Community Setup Administrator</title>
</head>

<body>
    <h2>Welcome to Church Community</h2>

    <p>
        Your setup administrator account has been created.
    </p>

    <p>
        <strong>Church:</strong>
        {{ $church->church_name }}
    </p>

    <p>
        <strong>Church Code:</strong>
        {{ $church->church_code }}
    </p>

    <p>
        <strong>Email:</strong>
        {{ $user->email }}
    </p>

    <p>
        <strong>Temporary Password:</strong>
        {{ $temporaryPassword }}
    </p>

    <p>
        Please use the temporary password to sign in.
        You will be required to change it when you first sign in.
    </p>

    <p>
        <strong>Important:</strong>
        Please do not share your temporary password with anyone.
    </p>
</body>

</html>
