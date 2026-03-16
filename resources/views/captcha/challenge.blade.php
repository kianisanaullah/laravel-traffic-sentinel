<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Check</title>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

        .wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            width: 100%;
            max-width: 520px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .header {
            background: #111827;
            color: #fff;
            padding: 22px 24px;
            font-size: 20px;
            font-weight: 700;
        }

        .body {
            padding: 24px;
        }

        .text {
            margin: 0 0 18px;
            line-height: 1.6;
            color: #4b5563;
        }

        .error {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 8px;
            background: #fee2e2;
            color: #991b1b;
            font-size: 14px;
        }

        .actions {
            margin-top: 20px;
        }

        .btn {
            display: inline-block;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 11px 18px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .muted {
            margin-top: 18px;
            font-size: 13px;
            color: #6b7280;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="header">
            Security Check
        </div>

        <div class="body">
            <p class="text">
                We detected unusual activity from your network. Please complete the verification below to continue to the requested page.
            </p>

            @if($errors->has('captcha'))
                <div class="error">
                    {{ $errors->first('captcha') }}
                </div>
            @endif

            <form method="POST" action="{{ route('traffic-sentinel.captcha.verify') }}">
                @csrf

                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">

                <div class="cf-turnstile"
                     data-sitekey="{{ $turnstileSiteKey }}"
                     data-callback="turnstileSuccess"
                     data-response-field-name="cf-turnstile-response">
                </div>

                <div class="actions">
                    <button id="verify-btn" type="submit" class="btn" disabled>
                        Verifying...
                    </button>
                </div>

            </form>

            <script>
                function turnstileSuccess(token) {

                    if(!token) {
                        return;
                    }

                    const form = document.querySelector('form');

                    if(!form.querySelector('[name="cf-turnstile-response"]')){
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'cf-turnstile-response';
                        input.value = token;
                        form.appendChild(input);
                    }

                    form.submit();
                }
            </script>
            <div class="muted">
                Traffic Sentinel protection is active on this site.
            </div>
        </div>
    </div>
</div>
</body>
</html>
