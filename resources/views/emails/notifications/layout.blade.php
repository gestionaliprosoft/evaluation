<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <style>
            body { background-color: #f6f9fc; font-family: Arial, sans-serif; padding: 20px; margin: 0; }
            .wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; }
            .logo-container { margin-bottom: 5px; text-align: center; }
            .logo-container img { max-height: 60px; width: auto; display: inline-block; }
            .header { background-color: #1e3a8a; padding: 20px; text-align: center; color: #ffffff; font-size: 20px; font-weight: bold; }
            .content { padding: 30px; color: #334155; font-size: 16px; line-height: 1.6; }
            .footer { background-color: #f8fafc; padding: 15px; text-align: center; color: #64748b; font-size: 12px; border-top: 1px solid #edf2f7; }
        </style>
    </head>
    <body>
        <div class="wrapper">
            @if($logo)
                <div class="logo-container">
                    <img src="{{ $message->embed($logo) }}" alt="logo">
                </div>
            @else
                <div class="header">
                    {{ auth()->user()->team->name }}
                </div>
            @endif

            <div class="content">
                {!! $mailContent !!}
            </div>

            <div class="footer">
                {{ __('email-template.automatic_send_message') }}.<br>
                © {{ date('Y') }} {{ auth()->user()->team->name }}. {{ __('email-template.all_rights_reserved') }}.
            </div>
        </div>
    </body>
</html>
