<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? '' }}</title>
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f3f4f6; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f3f4f6; padding-bottom: 40px; }
        .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-spacing: 0; color: #1f2937; }
        .header { background-color: #4f46e5; padding: 30px 20px; text-align: center; }
        .header img { max-height: 48px; margin-bottom: 12px; }
        .header-text { color: #ffffff; font-size: 24px; font-weight: 700; margin: 0; }
        .content { padding: 30px; }
        .content p { margin: 0 0 16px 0; line-height: 1.6; color: #374151; }
        .cta-button { display: inline-block; background-color: #4f46e5; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; margin: 16px 0; }
        .footer { background-color: #f9fafb; padding: 24px 20px; text-align: center; border-top: 1px solid #e5e7eb; }
        .footer p { margin: 4px 0; font-size: 12px; color: #6b7280; line-height: 1.5; }
        .footer a { color: #4f46e5; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <table class="main" style="width: 100%;">
            <tr>
                <td>
                    @include('emails.partials.header')
                </td>
            </tr>
            <tr>
                <td class="content">
                    {!! $body ?? '' !!}

                    @if(isset($cta_url) && isset($cta_text))
                        <p style="text-align: center; margin-top: 24px;">
                            <a href="{{ $cta_url }}" class="cta-button">{{ $cta_text }}</a>
                        </p>
                    @endif
                </td>
            </tr>
            <tr>
                <td>
                    @include('emails.partials.footer', ['show_unsubscribe' => $show_unsubscribe ?? false])
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
