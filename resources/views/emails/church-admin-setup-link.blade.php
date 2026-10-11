<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up Your Church Account</title>
</head>

<body style="margin:0; padding:0; background:#f3f4f6; font-family:Arial,Helvetica,sans-serif; color:#1f2937;">
    <div
        style="max-width:600px; margin:30px auto; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb;">

        <div style="background:#191970; padding:30px; text-align:center;">
            <h1 style="margin:0; color:#ffffff; font-size:24px;">
                Church Community
            </h1>
            <p style="margin:10px 0 0; color:#fcd34d; font-size:14px;">
                Administrator Account Setup
            </p>
        </div>

        <div style="padding:30px;">
            <p>Hello {{ $adminName ?: 'Church Administrator' }},</p>

            <p>
                Your church registration has been processed successfully.
                You can now set up your administrator account to manage
                your church through the Church Community platform.
            </p>

            <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:18px; margin:24px 0;">
                <p style="margin:0 0 10px;">
                    <strong>Church:</strong> {{ $churchName }}
                </p>
                <p style="margin:0 0 10px;">
                    <strong>Church Code:</strong> {{ $churchCode }}
                </p>
                <p style="margin:0;">
                    <strong>Administrator Email:</strong> {{ $adminEmail }}
                </p>
            </div>

            <p>
                Click the button below to create your administrator account
                and choose your password.
            </p>

            <div style="text-align:center; margin:30px 0;">
                <a href="{{ $setupUrl }}"
                    style="display:inline-block; background:#191970; color:#ffffff; text-decoration:none; padding:14px 28px; border-radius:7px; font-weight:bold;">
                    Set Up Administrator Account
                </a>
            </div>

            <p style="font-size:14px; color:#b45309;">
                <strong>Important:</strong> This secure link expires in
                {{ $expiresInHours }} hours and can be used only once.
            </p>

            <p style="font-size:14px;">
                If the button does not work, copy and paste this URL into
                your browser:
            </p>

            <p style="font-size:13px; word-break:break-all;">
                <a href="{{ $setupUrl }}" style="color:#191970;">
                    {{ $setupUrl }}
                </a>
            </p>

            <p>
                If you did not request this account, please contact the
                platform administrator.
            </p>

            <p style="margin-top:30px;">
                Regards,<br>
                <strong>Church Community Team</strong>
            </p>
        </div>

        <div style="background:#f9fafb; padding:18px; text-align:center; font-size:12px; color:#6b7280;">
            This is an automated email. Please do not reply directly.
        </div>
    </div>
</body>

</html>
