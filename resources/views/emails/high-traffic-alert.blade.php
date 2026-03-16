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
            background:#111827;
            color:#fff;
            padding:20px;
            font-size:18px;
            font-weight:bold;
        }

        .content{
            padding:25px;
        }

        .row{
            margin-bottom:12px;
        }

        .label{
            font-weight:bold;
            color:#555;
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

        .bot{
            background:#f59e0b;
            color:#fff;
        }

        .human{
            background:#3b82f6;
            color:#fff;
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

        .actions{
            margin-top:20px;
        }

        .button{
            display:inline-block;
            padding:10px 16px;
            margin-right:10px;
            text-decoration:none;
            font-size:13px;
            border-radius:5px;
        }

        .btn-monitor{
            background:#10b981;
            color:#fff;
        }

        .btn-block{
            background:#ef4444;
            color:#fff;
        }

        .btn-throttle{
            background:#f59e0b;
            color:#111;
        }

        .footer{
            padding:15px 25px;
            font-size:12px;
            color:#888;
            background:#fafafa;
        }

    </style>
</head>

<body>

<div class="wrapper">

    <div class="card">

        <div class="header">
            🚨 Traffic Sentinel Security Alert
        </div>

        <div class="content">

            <p>
                High traffic activity has been detected by <strong>Traffic Sentinel</strong>.
                This may indicate a crawler, bot, or suspicious activity.
            </p>

            <table class="table">

                <tr>
                    <td class="label">IP Address</td>
                    <td class="value">{{ $ip ?? 'Unknown' }}</td>
                </tr>

                <tr>
                    <td class="label">Traffic Type</td>
                    <td>
                        @if($trafficType === 'Bot')
                            <span class="badge bot">BOT</span>
                        @else
                            <span class="badge human">HUMAN</span>
                        @endif

                        @if(!empty($botName))
                            &nbsp;({{ $botName }})
                        @endif
                    </td>
                </tr>

                <tr>
                    <td class="label">Host</td>
                    <td>{{ $host ?? 'Unknown' }}</td>
                </tr>

                <tr>
                    <td class="label">Requests Detected</td>
                    <td>{{ number_format($hits ?? 0) }}</td>
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
                   style="
      display:inline-block;
      padding:10px 16px;
      background:#dc3545;
      color:white;
      text-decoration:none;
      border-radius:6px;
      font-weight:600;
   ">
                    🚫 Review / Block IP
                </a>

            </div>

        </div>

        <div class="footer">
            Traffic Sentinel Monitoring System<br>
            Generated automatically by the security monitoring service.
        </div>

    </div>

</div>

</body>
</html>
