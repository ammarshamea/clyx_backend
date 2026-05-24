<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mailSubject }}</title>
</head>
<body style="margin:0;padding:0;background:#0f0f14;font-family:'Segoe UI',Tahoma,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0f0f14;padding:24px 12px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#1a1a24;border-radius:12px;border:1px solid #2d2d3a;overflow:hidden;">
                <tr>
                    <td style="padding:20px 24px;background:linear-gradient(135deg,#6d28d9,#8b5cf6);">
                        <p style="margin:0;font-size:13px;color:rgba(255,255,255,0.85);">CLYX — إدارة المهام</p>
                        <h1 style="margin:8px 0 0;font-size:18px;color:#fff;font-weight:600;">{{ $mailSubject }}</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;color:#e5e7eb;font-size:15px;line-height:1.7;">
                        @if(!empty($recipientName))
                            <p style="margin:0 0 16px;">مرحباً <strong>{{ $recipientName }}</strong>،</p>
                        @endif
                        @if(!empty($eventLabel))
                            <p style="margin:0 0 12px;color:#a78bfa;font-size:13px;font-weight:600;">{{ $eventLabel }}</p>
                        @endif
                        <div style="margin:0 0 20px;white-space:pre-wrap;">{!! nl2br(e($mailBody)) !!}</div>
                        @if(!empty($taskLink))
                            <p style="margin:0 0 8px;">
                                <a href="{{ $taskLink }}" style="display:inline-block;padding:12px 20px;background:#7c3aed;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">عرض المهمة في لوحة التحكم</a>
                            </p>
                            <p style="margin:12px 0 0;font-size:12px;color:#6b7280;word-break:break-all;">{{ $taskLink }}</p>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 24px;border-top:1px solid #2d2d3a;color:#6b7280;font-size:12px;">
                        هذا بريد تلقائي من نظام مهام Clyx. لا ترد على هذا البريد.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
