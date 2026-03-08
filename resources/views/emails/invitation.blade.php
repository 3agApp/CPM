<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Invitation</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { background: #f8fafc; border-radius: 8px; padding: 32px; margin: 20px 0; }
        .btn { display: inline-block; background: #2563eb; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; }
        .footer { font-size: 13px; color: #64748b; margin-top: 32px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>You're Invited!</h2>

        <p>{{ $inviterName }} has invited you to join <strong>{{ $organizationName }}</strong> as a <strong>{{ $role }}</strong>.</p>

        <p>
            <a href="{{ $acceptUrl }}" class="btn">Accept Invitation</a>
        </p>

        <p class="footer">
            This invitation expires on {{ $expiresAt }}.<br>
            If you did not expect this invitation, you can safely ignore this email.
        </p>
    </div>
</body>
</html>
