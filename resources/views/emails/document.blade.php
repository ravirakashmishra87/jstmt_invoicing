<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $subjectLine ?? $companyName }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background: #e7eef5;
            font-family: "Segoe UI", Calibri, "Helvetica Neue", Arial, sans-serif;
            color: #1e293b;
        }
        a { color: #1e4e79; }
        .preheader {
            display: none !important;
            visibility: hidden;
            opacity: 0;
            color: transparent;
            height: 0;
            width: 0;
            overflow: hidden;
            mso-hide: all;
        }
        @media only screen and (max-width: 620px) {
            .email-shell { width: 100% !important; }
            .email-pad { padding-left: 18px !important; padding-right: 18px !important; }
            .meta-cell { display: block !important; width: 100% !important; padding-bottom: 12px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#e7eef5;">
@php
    $preheader = $preheader ?? \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', $bodyText)), 110);
    $typeLabel = $typeLabel ?? 'Correspondence';
@endphp
<div class="preheader">{{ $preheader }}</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#e7eef5;">
    <tr>
        <td align="center" style="padding:28px 14px;">
            <table role="presentation" class="email-shell" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;">
                {{-- Brand masthead --}}
                <tr>
                    <td style="background:linear-gradient(135deg,#0f2744 0%,#16365c 55%,#1e4e79 100%);background-color:#0f2744;border-radius:16px 16px 0 0;padding:0;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td class="email-pad" style="padding:28px 36px 24px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td valign="middle" style="width:52px;">
                                                <div style="width:44px;height:44px;border-radius:10px;background:#2f6fa3;color:#ffffff;font-family:Barlow,'Segoe UI',Arial,sans-serif;font-size:15px;font-weight:700;letter-spacing:.04em;text-align:center;line-height:44px;">
                                                    JST
                                                </div>
                                            </td>
                                            <td valign="middle" style="padding-left:14px;">
                                                <div style="font-family:Barlow,'Segoe UI',Arial,sans-serif;font-size:18px;font-weight:700;color:#ffffff;letter-spacing:.01em;line-height:1.25;">
                                                    {{ $companyName }}
                                                </div>
                                                @if(!empty($companyTagline))
                                                    <div style="margin-top:4px;font-size:12px;color:#93a9c0;letter-spacing:.08em;text-transform:uppercase;">
                                                        {{ $companyTagline }}
                                                    </div>
                                                @else
                                                    <div style="margin-top:4px;font-size:12px;color:#93a9c0;letter-spacing:.08em;text-transform:uppercase;">
                                                        Precision machine tools
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="height:4px;background:#2f6fa3;font-size:0;line-height:0;">&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Body card --}}
                <tr>
                    <td style="background:#ffffff;padding:0;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td class="email-pad" style="padding:32px 36px 8px;">
                                    <div style="display:inline-block;padding:6px 12px;border-radius:999px;background:#eef5fb;border:1px solid #c5d7ea;color:#1e4e79;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;">
                                        {{ $typeLabel }}
                                    </div>
                                </td>
                            </tr>

                            @if(!empty($meta))
                                <tr>
                                    <td class="email-pad" style="padding:18px 36px 6px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f8fc;border:1px solid #d5dee8;border-radius:12px;">
                                            <tr>
                                                @foreach($meta as $label => $value)
                                                    <td class="meta-cell" width="{{ (int) floor(100 / max(count($meta), 1)) }}%" valign="top" style="padding:16px 18px;">
                                                        <div style="font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#64748b;margin-bottom:6px;">{{ $label }}</div>
                                                        <div style="font-family:Barlow,'Segoe UI',Arial,sans-serif;font-size:15px;font-weight:700;color:#0f2744;line-height:1.35;">{{ $value }}</div>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            @endif

                            <tr>
                                <td class="email-pad" style="padding:22px 36px 10px;font-size:15px;line-height:1.7;color:#334155;">
                                    {!! nl2br(e($bodyText)) !!}
                                </td>
                            </tr>

                            @if(!empty($attachmentName))
                                <tr>
                                    <td class="email-pad" style="padding:8px 36px 10px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px dashed #9db8d1;border-radius:12px;background:#f8fbfe;">
                                            <tr>
                                                <td style="padding:14px 16px;font-size:13px;color:#16365c;">
                                                    <strong style="color:#0f2744;">PDF attached</strong>
                                                    <span style="color:#64748b;"> — {{ $attachmentName }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            @endif

                            <tr>
                                <td class="email-pad" style="padding:18px 36px 34px;">
                                    <div style="height:1px;background:#d5dee8;line-height:1px;font-size:0;">&nbsp;</div>
                                    <div style="margin-top:18px;font-size:13px;color:#64748b;line-height:1.6;">
                                        This message was sent by <strong style="color:#0f2744;">{{ $companyName }}</strong>.
                                        @if(!empty($companyContact))
                                            <br>{{ $companyContact }}
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background:#0f2744;border-radius:0 0 16px 16px;padding:18px 36px;">
                        <div style="font-size:12px;color:#93a9c0;line-height:1.55;text-align:center;">
                            {{ $footerNote ?: 'Confidential business correspondence. If you received this in error, please notify the sender and delete it.' }}
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
