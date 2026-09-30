<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light">
    <title>Happy Birthday from Your Church Family</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=Great+Vibes&family=Mulish:wght@400;600&display=swap"
        rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table {
            border-collapse: collapse;
            mso-table-lspace: 0;
            mso-table-rspace: 0;
        }

        a {
            color: #1F2A5A;
        }

        @media only screen and (max-width:620px) {
            .container {
                width: 100% !important;
            }

            .pad {
                padding-left: 24px !important;
                padding-right: 24px !important;
            }

            .hero-title {
                font-size: 44px !important;
                line-height: 48px !important;
            }

            .name {
                font-size: 30px !important;
            }

            .stack {
                display: block !important;
                width: 100% !important;
            }
        }
    </style>
</head>

<body style="margin:0; padding:0; background-color:#E8ECF5;">

    <!-- Preheader (hidden preview text) -->
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; font-size:1px; line-height:1px; color:#E8ECF5;">
        A special birthday blessing for you from our church family.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background-color:#E8ECF5;">
        <tr>
            <td align="center" style="padding:32px 12px;">

                <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0"
                    border="0"
                    style="width:600px; max-width:600px; background-color:#FFFFFF; border-radius:14px; overflow:hidden;">

                    <!-- Gold top band -->
                    <tr>
                        <td height="6" style="height:6px; line-height:6px; font-size:0; background-color:#C9A24B;">
                            &nbsp;</td>
                    </tr>

                    <!-- HERO -->
                    <tr>
                        <td align="center" class="pad" bgcolor="#1F2A5A"
                            style="background-color:#1F2A5A; background-image:linear-gradient(160deg,#2B3A7A 0%,#1F2A5A 55%,#141C42 100%); padding:44px 40px 40px 40px;">

                            <div
                                style="font-family:Georgia,'Times New Roman',serif; font-size:26px; line-height:26px; color:#C9A24B; padding-bottom:14px;">
                                @if ($church->logo)
                                    <div style="text-align: center; margin-bottom: 20px;">
                                        <img src="{{ 'https://csiadmin.csimarital.in/storage/' . $church->logo }}"
                                            alt="{{ $church->church_name }}"
                                            style="display:block; margin:0 auto; width:90px; max-width:90px; height:auto; border:0; outline:none; text-decoration:none;">
                                    </div>
                                @endif

                            </div>

                            <div
                                style="font-family:'Mulish',Arial,Helvetica,sans-serif; font-size:13px; letter-spacing:3px; color:#D9C48A; padding-bottom:18px;">
                                {{ $church->church_name }} </div>
                            </div>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td style="border-top:1px solid #C9A24B; width:60px; font-size:0; line-height:0;">
                                        &nbsp;</td>
                                    <td
                                        style="padding:0 12px; font-family:Georgia,serif; color:#C9A24B; font-size:14px;">
                                        &#10022;</td>
                                    <td style="border-top:1px solid #C9A24B; width:60px; font-size:0; line-height:0;">
                                        &nbsp;</td>
                                </tr>
                            </table>

                            <div class="hero-title"
                                style="font-family:'Great Vibes','Brush Script MT',cursive,Georgia,serif; font-size:62px; line-height:66px; color:#FFFFFF; padding-top:16px;">
                                Happy Birthday
                            </div>

                            <div class="name"
                                style="font-family:'Cormorant Garamond',Georgia,'Times New Roman',serif; font-size:36px; line-height:42px; font-weight:600; color:#F2D98D; padding-top:8px;">
                                {{ $name }},
                            </div>
                        </td>
                    </tr>

                    <!-- Gold scallop divider -->
                    <tr>
                        <td height="4"
                            style="height:4px; line-height:4px; font-size:0; background-color:#C9A24B; background-image:linear-gradient(90deg,#B08A34,#E6C978,#B08A34);">
                            &nbsp;</td>
                    </tr>

                    <!-- SCRIPTURE -->
                    <tr>
                        <td align="center" class="pad" style="padding:40px 48px 8px 48px; background-color:#FBFAF6;">
                            <div
                                style="font-family:'Cormorant Garamond',Georgia,serif; font-style:italic; font-size:24px; line-height:34px; color:#1F2A5A; font-weight:500;">
                                &ldquo;The Lord bless thee, and keep thee: the Lord make his face shine upon thee, and
                                be gracious unto thee.&rdquo;
                            </div>
                            <div
                                style="font-family:'Mulish',Arial,sans-serif; font-size:13px; letter-spacing:2px; color:#B08A34; padding-top:12px; font-weight:600;">
                                NUMBERS 6:24&ndash;25
                            </div>
                        </td>
                    </tr>

                    <!-- MESSAGE -->
                    <tr>
                        <td class="pad" style="padding:28px 48px 12px 48px; background-color:#FBFAF6;">
                            <p
                                style="margin:0 0 18px 0; font-family:'Mulish',Arial,Helvetica,sans-serif; font-size:16px; line-height:28px; color:#3B4260;">
                                Grace and peace to you in the name of our Lord Jesus Christ.
                            </p>
                            <p
                                style="margin:0 0 18px 0; font-family:'Mulish',Arial,Helvetica,sans-serif; font-size:16px; line-height:28px; color:#3B4260;">
                                On this special day, I join the entire church family in celebrating the gift of your
                                life. God knew you before the foundations of the earth, and every year He has kept you
                                is a testimony of His faithfulness and love.
                            </p>
                            <p
                                style="margin:0 0 18px 0; font-family:'Mulish',Arial,Helvetica,sans-serif; font-size:16px; line-height:28px; color:#3B4260;">
                                Thank you for your prayers, your service and your presence among us. The church is
                                richer because you are part of it.
                            </p>
                            <p
                                style="margin:0; font-family:'Mulish',Arial,Helvetica,sans-serif; font-size:16px; line-height:28px; color:#3B4260;">
                                As you begin a new year, may the Lord renew your strength, crown your days with joy,
                                open new doors of favour, and keep you and your household in good health and in His
                                perfect peace.
                            </p>
                        </td>
                    </tr>

                    <!-- BLESSING CARD -->
                    <tr>
                        <td class="pad" style="padding:24px 48px 8px 48px; background-color:#FBFAF6;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td
                                        style="background-color:#FFFFFF; border:1px solid #E6D9B0; border-left:5px solid #C9A24B; border-radius:8px; padding:22px 26px;">
                                        <div
                                            style="font-family:'Cormorant Garamond',Georgia,serif; font-size:22px; line-height:28px; font-weight:700; color:#1F2A5A; padding-bottom:8px;">
                                            My prayer for you this year
                                        </div>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                            border="0">
                                            <tr>
                                                <td class="stack" width="50%" valign="top"
                                                    style="font-family:'Mulish',Arial,sans-serif; font-size:15px; line-height:26px; color:#3B4260; padding-right:10px;">
                                                    <span style="color:#C9A24B;">&#10022;</span>&nbsp; Good health and
                                                    strength<br>
                                                    <span style="color:#C9A24B;">&#10022;</span>&nbsp; Joy that lasts
                                                    all year
                                                </td>
                                                <td class="stack" width="50%" valign="top"
                                                    style="font-family:'Mulish',Arial,sans-serif; font-size:15px; line-height:26px; color:#3B4260;">
                                                    <span style="color:#C9A24B;">&#10022;</span>&nbsp; Favour in all
                                                    your endeavours<br>
                                                    <span style="color:#C9A24B;">&#10022;</span>&nbsp; Peace that
                                                    surpasses understanding
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- SIGN-OFF -->
                    <tr>
                        <td align="center" class="pad"
                            style="padding:32px 48px 40px 48px; background-color:#FBFAF6;">
                            <div
                                style="font-family:'Mulish',Arial,sans-serif; font-size:15px; line-height:24px; color:#3B4260;">
                                With love and prayers,
                            </div>
                            <div
                                style="font-family:'Great Vibes','Brush Script MT',cursive,Georgia,serif; font-size:42px; line-height:52px; color:#1F2A5A; padding-top:6px;">
                                {{ $presbyter->name ?? 'Your Pastor' }}
                            </div>
                            <div
                                style="font-family:'Cormorant Garamond',Georgia,serif; font-size:19px; line-height:26px; color:#B08A34; font-weight:600;">
                                Presbyter-in-Charge
                            </div>
                            <div
                                style="font-family:'Mulish',Arial,sans-serif; font-size:14px; line-height:22px; color:#6A7090;">
                                {{ $church->name ?? 'Your Church' }}
                            </div>
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td align="center" class="pad" bgcolor="#141C42"
                            style="background-color:#141C42; padding:28px 40px;">
                            <div
                                style="font-family:'Cormorant Garamond',Georgia,serif; font-style:italic; font-size:18px; line-height:26px; color:#F2D98D; padding-bottom:10px;">
                                &ldquo;Many blessings, one family in Christ.&rdquo;
                            </div>
                            <div
                                style="font-family:'Mulish',Arial,sans-serif; font-size:12px; line-height:20px; color:#AEB6DA;">
                                {{ $church->church_name }} &bull; {{ $church->address }} {{ $churh->city }}<br>
                                &bull; <a href="mailto:office@yourchurch.org"
                                    style="color:#D9C48A; text-decoration:underline;">{{ $church->email ?? '' }}</a>
                            </div>
                            <div
                                style="font-family:'Mulish',Arial,sans-serif; font-size:11px; line-height:18px; color:#8790BD; padding-top:14px;">
                                You are receiving this because you are a member of our congregation.<br>

                            </div>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
