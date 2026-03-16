<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <style>

        body{
            margin:0;
            padding:0;
            background:#f4f6f8;
            font-family: Arial, Helvetica, sans-serif;
        }

        .wrapper{
            padding:30px;
        }

        .card{
            max-width:700px;
            margin:auto;
            background:#ffffff;
            border-radius:10px;
            overflow:hidden;
            box-shadow:0 6px 20px rgba(0,0,0,0.08);
        }

        .header{
            background:#7f1d1d;
            color:#fff;
            padding:20px;
            font-size:18px;
            font-weight:bold;
        }

        .content{
            padding:25px;
        }

        .table{
            width:100%;
            border-collapse:collapse;
            margin-top:15px;
        }

        .table td{
            padding:10px;
            border-bottom:1px solid #eee;
        }

        .label{
            font-weight:bold;
            color:#555;
            width:180px;
        }

        .value{
            color:#111;
        }

        .badge{
            display:inline-block;
            padding:4px 10px;
            border-radius:4px;
            font-size:12px;
            font-weight:bold;
        }

        .blocked{
            background:#ef4444;
            color:#fff;
        }

        .footer{
            padding:15px 25px;
            font-size:12px;
            color:#888;
            background:#fafafa;
        }

        .actions{
            margin-top:20px;
        }

        .button{
            display:inline-block;
            padding:10px 16px;
            text-decoration:none;
            font-size:13px;
            border-radius:6px;
            font-weight:600;
        }

        .btn-review{
            background:#dc3545;
            color:#fff;
        }

    </style>
</head>

<body>

<div class="wrapper">

    <div class="card">

        <div class="header">
            🚫 Traffic Sentinel Security Alert — IP Blocked
        </div>

        <div class="content">

            <p>
                Traffic Sentinel has automatically <strong>blocked an IP address</strong> after repeated
                <strong>CAPTCHA verification failures</strong>.
            </p>

            <p>
                This may indicate a malicious bot, automated scraping tool, or suspicious activity.
            </p>

            <table class="table">

                <tr>
                    <td class="label">IP Address</td>
                    <td class="value">{{ $ip ?? 'Unknown' }}</td>
                </tr>

                <tr>
                    <td class="label">Status</td>
                    <td>
                        <span class="badge blocked">BLOCKED</span>
                    </td>
                </tr>

                <tr>
                    <td class="label">Reason</td>
                    <td>Repeated CAPTCHA failures</td>
                </tr>

                <tr>
                    <td class="label">Host</td>
                    <td>{{ $host ?? request()->getHost() }}</td>
                </tr>

                <tr>
                    <td class="label">Time</td>
                    <td>{{ $time }}</td>
                </tr>

            </table>

            <div class="actions">

                @php
                    $ipUrl = route('traffic-sentinel.ips.show', $ip);
                @endphp

                <a href="{{ $ipUrl }}"
                   class="button btn-review">
                    🔎 Review IP Activity
                </a>

            </div>

        </div>

        <div class="footer">
            Traffic Sentinel Monitoring System<br>
            This alert was generated automatically by the security monitoring service.
        </div>

    </div>

</div>

</body>
</html>
