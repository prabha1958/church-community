<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">

    <title>New Church Registration Request</title>
</head>

<body style="margin:0; padding:0; background:#f4f6f8; font-family:Arial, Helvetica, sans-serif;">

    <div style="max-width:650px; margin:30px auto; background:#ffffff; border-radius:8px; overflow:hidden;">

        <!-- Header -->
        <div style="background:#191970; padding:25px; text-align:center;">
            <h1 style="margin:0; color:#ffffff; font-size:24px;">
                New Church Registration Request
            </h1>
        </div>

        <!-- Content -->
        <div style="padding:30px;">

            <p style="font-size:16px; color:#333333;">
                A new church registration request has been submitted through
                the Church Message App.
            </p>

            <!-- Church Details -->
            <h2 style="color:#191970; font-size:19px; margin-top:25px;">
                Church Details
            </h2>

            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; font-size:15px;">

                <tr>
                    <td width="35%" style="font-weight:bold; border-bottom:1px solid #eeeeee;">
                        Church Name
                    </td>
                    <td style="border-bottom:1px solid #eeeeee;">
                        {{ $registrationRequest->church_name }}
                    </td>
                </tr>

                <tr>
                    <td style="font-weight:bold; border-bottom:1px solid #eeeeee;">
                        Address
                    </td>
                    <td style="border-bottom:1px solid #eeeeee;">
                        {{ $registrationRequest->address }}
                    </td>
                </tr>

                <tr>
                    <td style="font-weight:bold; border-bottom:1px solid #eeeeee;">
                        City
                    </td>
                    <td style="border-bottom:1px solid #eeeeee;">
                        {{ $registrationRequest->city }}
                    </td>
                </tr>

            </table>

            <!-- Administrator Details -->
            <h2 style="color:#191970; font-size:19px; margin-top:30px;">
                Church In-Charge Details
            </h2>

            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; font-size:15px;">

                <tr>
                    <td width="35%" style="font-weight:bold; border-bottom:1px solid #eeeeee;">
                        Name
                    </td>
                    <td style="border-bottom:1px solid #eeeeee;">
                        {{ $registrationRequest->admin_name }}
                    </td>
                </tr>

                <tr>
                    <td style="font-weight:bold; border-bottom:1px solid #eeeeee;">
                        Email
                    </td>
                    <td style="border-bottom:1px solid #eeeeee;">
                        <a href="mailto:{{ $registrationRequest->admin_email }}">
                            {{ $registrationRequest->admin_email }}
                        </a>
                    </td>
                </tr>

            </table>

            <!-- Request Details -->
            <h2 style="color:#191970; font-size:19px; margin-top:30px;">
                Request Details
            </h2>

            <p style="font-size:15px; color:#555555;">
                <strong>Request ID:</strong>
                #{{ $registrationRequest->id }}
            </p>

            <p style="font-size:15px; color:#555555;">
                <strong>Status:</strong>
                {{ ucfirst($registrationRequest->status) }}
            </p>

            <p style="font-size:15px; color:#555555;">
                <strong>Submitted:</strong>
                {{ $registrationRequest->created_at->format('d M Y, h:i A') }}
            </p>

            <!-- Action -->
            <div style="margin-top:30px; padding:18px; background:#fff8e1; border-left:4px solid #FFD700;">
                <p style="margin:0; color:#555555;">
                    Please review this registration request and contact the
                    church in-charge before approving the application.
                </p>
            </div>

        </div>

        <!-- Footer -->
        <div style="background:#f4f6f8; padding:20px; text-align:center;">

            <p style="margin:0; font-size:13px; color:#777777;">
                Church Message App
            </p>

            <p style="margin:6px 0 0; font-size:12px; color:#999999;">
                This is an automated notification.
            </p>

        </div>

    </div>

</body>

</html>
