<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Broadcast Email</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f3f6fa;
            font-family: Arial, Helvetica, sans-serif;
            color: #1e293b;
            -webkit-font-smoothing: antialiased
        }

        table {
            border-collapse: collapse
        }

        img {
            border: 0
        }

        a {
            color: #0d5fa8;
            text-decoration: none
        }

        .shell {
            width: 100%;
            background: #f3f6fa;
            padding: 38px 16px
        }

        .card {
            max-width: 620px;
            margin: auto;
            background: #ffffff;
            border: 1px solid #e5eaf1;
            border-radius: 14px;
            overflow: hidden
        }

        .header {
            background: #fff;
            padding: 27px 38px;
            border-bottom: 1px solid #ebeff5
        }

        .brand {
            font-size: 19px;
            font-weight: 700;
            letter-spacing: -.3px;
            color: #182940
        }

        .brand-mark {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            color: #fff;
            background: #0d5fa8;
            border-radius: 8px;
            margin-right: 9px;
            font-size: 17px
        }

        .eyebrow {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.1px;
            color: #738396;
            text-transform: uppercase
        }

        .main {
            padding: 36px 38px 34px
        }

        .pill {
            display: inline-block;
            padding: 6px 11px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 100px;
            background: #fff3df;
            color: #965a08;
            margin-right: 7px
        }

        .pill-blue {
            background: #eaf3fc;
            color: #0d5fa8
        }

        .title {
            font-size: 28px;
            line-height: 1.24;
            letter-spacing: -.7px;
            color: #142539;
            margin: 19px 0 13px;
            font-weight: 700
        }

        .intro {
            color: #5a6a7d;
            font-size: 14px;
            line-height: 1.8;
            margin: 0 0 25px
        }

        .separator {
            height: 1px;
            background: #e8edf3;
            margin: 27px 0
        }

        .content {
            font-size: 14px;
            line-height: 1.85;
            color: #35445a
        }

        .content p {
            margin: 0 0 13px
        }

        .content ul {
            padding-left: 20px;
            margin: 0 0 16px
        }

        .content li {
            margin: 0 0 6px
        }

        .section-label {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
            color: #53677f;
            margin: 0 0 12px
        }

        .datebox {
            background: #f7f9fc;
            border: 1px solid #e8eef5;
            border-radius: 9px;
            padding: 16px 18px;
            margin-top: 25px
        }

        .date-label {
            font-size: 11px;
            font-weight: 700;
            color: #7b889a;
            letter-spacing: .3px
        }

        .date-text {
            font-size: 13px;
            color: #20344d;
            font-weight: 600;
            margin-top: 5px
        }

        .attach {
            border: 1px solid #e6ecf4;
            border-radius: 9px;
            padding: 13px 15px;
            margin: 9px 0
        }

        .fileicon {
            display: inline-block;
            background: #edf4fd;
            border-radius: 6px;
            padding: 8px;
            color: #0d5fa8;
            font-size: 14px;
            margin-right: 10px
        }

        .filename {
            font-size: 13px;
            font-weight: 700;
            color: #24405e
        }

        .filesize {
            font-size: 11px;
            color: #778698;
            margin-top: 5px
        }

        .button {
            display: inline-block;
            background: #0d5fa8;
            color: #fff !important;
            border-radius: 8px;
            padding: 15px 25px;
            font-weight: 700;
            font-size: 13px;
            margin: 29px 0 5px
        }

        .note {
            font-size: 12px;
            line-height: 1.7;
            color: #7a8999;
            margin: 13px 0 0
        }

        .footer {
            background: #f9fbfd;
            border-top: 1px solid #e8edf3;
            padding: 23px 38px;
            font-size: 12px;
            color: #718094;
            line-height: 1.7
        }

        .footer strong {
            color: #435873
        }

        @media only screen and (max-width:640px) {
            .shell {
                padding: 12px 8px
            }

            .main {
                padding: 27px 22px
            }

            .header,
            .footer {
                padding: 20px 22px
            }

            .title {
                font-size: 24px
            }
        }
    </style>
</head>

<body>
    <div class="shell">
        <div class="card">
            <div class="header">
                <div class="brand"><span class="brand-mark">B</span>{{ config('app.name', 'Tomsher PMS') }}</div>
            </div>
            <div class="main">
                <div class="eyebrow">Company broadcast</div>
                <h1 class="title">{{ $broadcast->title ?? 'Annual Team Meeting & Updates' }}</h1>
                <div style="margin-bottom:22px">
                    @if (!empty($categoryName))
                        <span class="pill pill-blue" style="{{ !empty($categoryColor) ? 'background-color: ' . $categoryColor . '20; color: ' . $categoryColor . ';' : '' }}">{{ $categoryName }}</span>
                    @elseif (!isset($broadcast))
                        <span class="pill pill-blue">Events</span>
                    @endif
                    <span class="pill">{{ $priorityLabel ?? 'Important' }}</span>
                </div>
                <p class="intro">Hello {{ $recipient->name ?? 'Alex' }},<br>This broadcast has been published for your audience. Please review the information below.</p>
                <div class="separator"></div>
                <div class="content">
                    @if (isset($contentHtml))
                        {!! $contentHtml !!}
                    @endif
                </div>
                @if (!empty($dateRangeFormatted))
                    <div class="datebox">
                        <div class="date-label">BROADCAST PERIOD</div>
                        <div class="date-text">{{ $dateRangeFormatted }}</div>
                    </div>
                @elseif (!isset($broadcast))
                    <div class="datebox">
                        <div class="date-label">BROADCAST PERIOD</div>
                        <div class="date-text">13 Nov 2026 — 16 Nov 2026</div>
                    </div>
                @endif
                @if (!empty($attachmentsList) && count($attachmentsList) > 0)
                    <div class="separator"></div>
                    <p class="section-label">Attachments</p>
                    @foreach ($attachmentsList as $item)
                        <div class="attach">
                            <span class="fileicon">▤</span>
                            <span class="filename">{{ $item['name'] }}</span>
                            @if (!empty($item['size']))
                                <span class="filesize"> · {{ $item['size'] }}</span>
                            @endif
                        </div>
                    @endforeach
                @endif
                <a class="button" href="{{ $broadcastUrl ?? '#broadcast' }}">View in PMS &rarr;</a>
                <p class="note">You are receiving this email because this broadcast was shared with you or your team.</p>
            </div>
            <div class="footer"><strong>{{ config('app.name', 'Tomsher PMS') }}</strong><br>This is an automated message from {{ config('app.name', 'Tomsher PMS') }}. Please do not reply to this email.</div>
        </div>
    </div>
</body>

</html>
