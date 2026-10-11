```html
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Church Message App</title>
</head>

<body
    style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: Arial, Helvetica, sans-serif; color: #334155;">

    @php
        $appUrl = rtrim(config('app.url'), '/');
        $loginUrl = $appUrl . '/platform/dashboard';
        $logoUrl = $appUrl . '/storage/logo.png';
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
        style="background-color: #f3f4f6; padding: 30px 12px;">
        <tr>
            <td align="center">

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                    style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden;">

                    <!-- Header -->
                    <tr>
                        <td align="center" style="background-color: #191970; padding: 32px 24px;">

                            <img src="{{ $logoUrl }}" alt="Church Message App" width="76" height="76"
                                style="display: block; width: 76px; height: 76px; object-fit: contain; margin: 0 auto 16px; border-radius: 12px;">

                            <h1 style="margin: 0; color: #ffffff; font-size: 25px; font-weight: 700;">
                                Church Message App
                            </h1>

                            <p style="margin: 8px 0 0; color: #e0e7ff; font-size: 14px;">
                                Your Church Community, Connected
                            </p>
                        </td>
                    </tr>

                    <!-- Welcome Message -->
                    <tr>
                        <td style="padding: 32px 30px 16px;">

                            <h2 style="margin: 0 0 16px; color: #191970; font-size: 23px;">
                                Welcome, Setup Administrator!
                            </h2>

                            <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.7;">
                                Your setup administrator account has been created successfully.
                                You can now sign in to manage your church's Church Message App setup.
                            </p>

                            <p style="margin: 0; font-size: 15px; line-height: 1.7;">
                                Please keep the following church and account details for your records.
                            </p>

                        </td>
                    </tr>

                    <!-- Church Details -->
                    <tr>
                        <td style="padding: 8px 30px 20px;">

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">

                                <tr>
                                    <td colspan="2" style="padding: 16px 18px 8px;">
                                        <h3 style="margin: 0; color: #191970; font-size: 16px;">
                                            Church Details
                                        </h3>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding: 8px 18px; color: #64748b; font-size: 14px;">
                                        Church Name
                                    </td>
                                    <td
                                        style="padding: 8px 18px; color: #1e293b; font-size: 14px; font-weight: 600; text-align: right;">
                                        {{ $church->church_name }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding: 8px 18px 16px; color: #64748b; font-size: 14px;">
                                        Church Code
                                    </td>
                                    <td
                                        style="padding: 8px 18px 16px; color: #191970; font-size: 14px; font-weight: 700; text-align: right;">
                                        {{ $church->church_code }}
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Login Credentials -->
                    <tr>
                        <td style="padding: 0 30px 24px;">

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                style="border: 1px solid #e2e8f0; border-radius: 8px;">

                                <tr>
                                    <td colspan="2" style="padding: 16px 18px 8px;">
                                        <h3 style="margin: 0; color: #191970; font-size: 16px;">
                                            Your Login Details
                                        </h3>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding: 8px 18px; color: #64748b; font-size: 14px;">
                                        Email Address
                                    </td>
                                    <td
                                        style="padding: 8px 18px; color: #1e293b; font-size: 14px; font-weight: 600; text-align: right; word-break: break-word;">
                                        {{ $user->email }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding: 8px 18px 16px; color: #64748b; font-size: 14px;">
                                        Temporary Password
                                    </td>
                                    <td
                                        style="padding: 8px 18px 16px; color: #191970; font-size: 14px; font-weight: 700; text-align: right; word-break: break-word;">
                                        {{ $temporaryPassword }}
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Login Button -->
                    <tr>
                        <td align="center" style="padding: 0 30px 28px;">

                            <p style="margin: 0 0 20px; font-size: 14px; line-height: 1.7;">
                                Click below to access your setup administrator dashboard.
                            </p>

                            <a href="{{ $loginUrl }}"
                                style="display: inline-block; background-color: #191970; color: #ffffff; text-decoration: none; padding: 15px 32px; border-radius: 7px; font-size: 15px; font-weight: 700;">
                                Sign In to Your Dashboard
                            </a>

                            <p style="margin: 18px 0 0; font-size: 12px; line-height: 1.6; color: #64748b;">
                                If the button does not work, copy this link into your browser:
                                <br>
                                <a href="{{ $loginUrl }}" style="color: #191970; word-break: break-all;">
                                    {{ $loginUrl }}
                                </a>
                            </p>

                        </td>
                    </tr>

                    <!-- Security Notice -->
                    <tr>
                        <td style="padding: 0 30px 28px;">

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                style="background-color: #fffbeb; border-left: 4px solid #f59e0b;">

                                <tr>
                                    <td style="padding: 16px;">
                                        <p style="margin: 0 0 8px; color: #92400e; font-size: 14px; font-weight: 700;">
                                            Important Security Notice
                                        </p>

                                        <p style="margin: 0; color: #78350f; font-size: 13px; line-height: 1.7;">
                                            Your temporary password is confidential. Please sign in and change
                                            it as soon as possible. Never share your password with anyone.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center"
                            style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 22px 24px;">

                            <p style="margin: 0 0 8px; color: #191970; font-size: 14px; font-weight: 700;">
                                Church Message App
                            </p>

                            <p style="margin: 0; color: #64748b; font-size: 12px; line-height: 1.7;">
                                Connecting churches and communities.
                                <br>
                                This is an automated account notification. Please do not reply to this email.
                            </p>

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
```
