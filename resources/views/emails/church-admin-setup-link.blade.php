<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">

    <title>
        Administrator Setup
    </title>
</head>

<body style="
        font-family: Arial, sans-serif;
        line-height: 1.6;
        color: #222;
    ">

    <h2>
        Your Church Community account is ready
    </h2>

    <p>
        Dear {{ $registrationRequest->admin_name }},
    </p>

    <p>
        Your church has been successfully registered for the
        Church Community App.
    </p>

    <h3>
        Church Details
    </h3>

    <p>
        <strong>Church:</strong>
        {{ $church->church_name }}
    </p>

    <p>
        <strong>Church Code:</strong>
        {{ $church->church_code }}
    </p>

    <p>
        <strong>Administrator:</strong>
        {{ $registrationRequest->admin_name }}
    </p>

    <p>
        Please use the button below to create your administrator
        password and complete the setup.
    </p>

    <p style="margin: 30px 0;">
        <a href="{{ $setupUrl }}"
            style="
                display: inline-block;
                padding: 12px 22px;
                background: #191970;
                color: #ffffff;
                text-decoration: none;
                border-radius: 6px;
                font-weight: bold;
            ">
            Complete Administrator Setup
        </a>
    </p>

    <p>
        This setup link is valid for
        <strong>24 hours</strong>
        and can only be used once.
    </p>

    <p>
        If you did not request this registration, please ignore
        this email.
    </p>

    <p>
        Regards,<br>
        Church Community App
    </p>

</body>

</html>
