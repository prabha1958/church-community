<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=Great+Vibes&family=Mulish:wght@400;600&display=swap"
        rel="stylesheet">

    <title>Happy Wedding Anniversary</title>

    <style>
        body {
            font-family: 'Mulish', Arial, Helvetica, sans-serif;
        }

        .church-name {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-weight: 700;
        }

        .anniversary-label {
            font-family: 'Mulish', Arial, sans-serif;
        }

        .title {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-weight: 700;
        }

        .couple-name {
            font-family: 'Great Vibes', 'Brush Script MT', cursive;
            font-size: 34px;
        }

        .scripture-text {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-size: 19px;
        }

        .presbyter-name {
            font-family: 'Cormorant Garamond', Georgia, serif;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6fb;
            font-family: 'Mulish', Arial, sans-serif;
            color: #333333;
        }

        table {
            border-spacing: 0;
            border-collapse: collapse;
        }

        .wrapper {
            width: 100%;
            background-color: #f4f6fb;
            padding: 30px 0;
        }

        .container {
            width: 100%;
            max-width: 680px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
        }

        .header {
            background-color: #191970;
            padding: 30px 25px 20px;
            text-align: center;
        }

        .logo {
            max-width: 130px;
            max-height: 110px;
            width: auto;
            height: auto;
            margin-bottom: 12px;
        }

        .church-name {
            color: #ffffff;
            font-size: 22px;
            font-weight: bold;
            margin: 0;
        }

        .anniversary-label {
            color: #FFD700;
            font-size: 14px;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 10px;
        }

        .hero {
            text-align: center;
            padding: 35px 30px 20px;
        }

        .confetti {
            font-size: 38px;
            letter-spacing: 7px;
            margin-bottom: 10px;
        }

        .title {
            color: #191970;
            font-size: 32px;
            line-height: 1.2;
            margin: 0 0 12px;
            font-weight: bold;
            font-family: 'Cormorant Garamond', Georgia, serif;
        }

        .couple-name {
            color: #9a6b00;
            font-size: 22px;
            font-weight: bold;
            margin: 0;
            font-family: 'Great Vibes', 'Brush Script MT', cursive;
        }

        .message {
            padding: 10px 45px 25px;
            text-align: center;
        }

        .message p {
            font-size: 16px;
            line-height: 1.75;
            margin: 0 0 17px;
            color: #444444;
        }

        .scripture {
            margin: 10px 35px 30px;
            padding: 22px 25px;
            background-color: #f8f6ec;
            border-left: 4px solid #FFD700;
            border-radius: 6px;
            text-align: center;
        }

        .scripture-text {
            color: #191970;
            font-size: 16px;
            line-height: 1.7;
            font-style: italic;
        }

        .scripture-reference {
            margin-top: 10px;
            color: #8a6d00;
            font-size: 14px;
            font-weight: bold;
        }

        .presbyter {
            text-align: center;
            padding: 10px 30px 35px;
        }

        .presbyter-photo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #FFD700;
            margin-bottom: 12px;
        }

        .presbyter-name {
            color: #191970;
            font-size: 18px;
            font-weight: bold;
            margin: 0;
        }

        .presbyter-title {
            color: #777777;
            font-size: 14px;
            margin-top: 5px;
        }

        .signature {
            color: #444444;
            font-size: 15px;
            margin-top: 15px;
            line-height: 1.5;
        }

        .footer {
            background-color: #191970;
            padding: 22px 25px;
            text-align: center;
        }

        .footer p {
            color: #ffffff;
            font-size: 12px;
            line-height: 1.6;
            margin: 4px 0;
        }

        .gold {
            color: #FFD700;
        }

        @media only screen and (max-width: 600px) {
            .wrapper {
                padding: 10px 0;
            }

            .container {
                border-radius: 0;
            }

            .title {
                font-size: 27px;
            }

            .couple-name {
                font-size: 20px;
            }

            .message {
                padding-left: 25px;
                padding-right: 25px;
            }

            .scripture {
                margin-left: 20px;
                margin-right: 20px;
            }
        }
    </style>
</head>

<body>

    <table role="presentation" width="100%" class="wrapper">
        <tr>
            <td align="center">

                <table role="presentation" class="container">

                    {{-- Church Header --}}
                    <tr>
                        <td class="header">

                            @if (!empty($churchLogoUrl))
                                <img src="{{ 'http://localhost:8000/storage/' . $church->logo }}"
                                    alt="{{ $church->church_name }}" class="logo">
                            @endif

                            <p class="church-name">
                                {{ $church->church_name }}
                            </p>

                            <div class="anniversary-label">
                                Wedding Anniversary Blessings
                            </div>

                        </td>
                    </tr>

                    {{-- Celebration --}}
                    <tr>
                        <td class="hero">

                            <div class="confetti">
                                🎉 🎊 💐 🎊 🎉
                            </div>

                            <h1 class="title">
                                Happy Wedding Anniversary!
                            </h1>

                            <p class="couple-name">
                                {{ $member->first_name }}

                                @if (!empty($member->spouse_name))
                                    &amp; {{ $member->spouse_name }}
                                @endif
                            </p>

                        </td>
                    </tr>

                    {{-- Main Message --}}
                    <tr>
                        <td class="message">

                            <p>
                                Dear
                                <strong>{{ $member->first_name }}</strong>
                                @if (!empty($member->spouse_name))
                                    and <strong>{{ $member->spouse_name }}</strong>
                                @endif,
                            </p>

                            <p>
                                Grace and peace to you in the precious name of our
                                Lord Jesus Christ.
                            </p>

                            <p>
                                On this beautiful occasion of your wedding
                                anniversary, I extend my warmest greetings and
                                prayers to you both on behalf of
                                <strong>{{ $church->church_name }}</strong>.
                            </p>

                            <p>
                                We thank God for the years of love, companionship,
                                faithfulness and togetherness that He has blessed
                                you with. May the Lord continue to strengthen the
                                bond you share and guide you as you walk together
                                in His grace.
                            </p>

                            <p>
                                May your home continue to be filled with love,
                                understanding, peace and the joy of the Lord.
                                May God bless you with many more wonderful years
                                together and make your family a testimony of His
                                unfailing faithfulness.
                            </p>

                        </td>
                    </tr>


                    {{-- Closing --}}
                    <tr>
                        <td class="message">

                            <p>
                                May the years ahead bring you renewed strength,
                                deeper love, good health, abundant joy and
                                countless reasons to thank God.
                            </p>

                            <p>
                                <strong>
                                    Wishing you both a very Happy Wedding
                                    Anniversary!
                                </strong>
                            </p>

                            <p>
                                May God's richest blessings rest upon you,
                                your marriage and your entire household.
                            </p>

                        </td>
                    </tr>

                    {{-- Presbyter --}}
                    <tr>
                        <td class="presbyter">

                            @if (!empty($presbyterPhotoUrl))
                                <img src="{{ 'https://csiadmin.csimarital.in/storage/' . $presbyter->photo }}"
                                    alt="{{ $presbyter->name ?? 'Presbyter-in-Charge' }}" class="presbyter-photo">
                            @endif

                            @if (!empty($presbyter))
                                <p class="presbyter-name">
                                    {{ $presbyter->name }}
                                </p>
                            @endif

                            <p class="presbyter-title">
                                Presbyter-in-Charge
                            </p>

                            <p class="signature">
                                With prayers and warm wishes,<br>
                                <strong>
                                    {{ $presbyter->name ?? 'Presbyter-in-Charge' }}
                                </strong><br>
                                {{ $church->church_name }}
                            </p>

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td class="footer">

                            <p>
                                <span class="gold">
                                    {{ $church->church_name }}
                                </span>
                            </p>

                            <p>
                                Celebrating God's blessings in your family
                            </p>

                            <p>
                                This greeting has been sent through the
                                Church Message App.
                            </p>

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
