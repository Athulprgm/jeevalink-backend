<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header { background-color: #dc2626; padding: 30px 20px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
        .content { padding: 30px; color: #374151; line-height: 1.6; }
        .button { display: inline-block; padding: 12px 24px; background-color: #dc2626; color: white !important; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 20px 0; }
        .footer { padding: 20px; text-align: center; font-size: 14px; color: #6b7280; background-color: #f9fafb; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>JeevaLink</h1>
        </div>
        <div class="content">
            <h2>Reset Your Password</h2>
            <p>Hello,</p>
            <p>We received a request to reset your password for your JeevaLink account. Click the button below to choose a new password:</p>
            
            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="button">Reset Password</a>
            </div>
            
            <p>If the button doesn't work, you can copy and paste the following link into your browser:</p>
            <p style="word-break: break-all; font-size: 14px; color: #4b5563;">{{ $resetUrl }}</p>
            
            <p>If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p>
            
            <p>Thank you,<br>The JeevaLink Team</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} JeevaLink. All rights reserved.
        </div>
    </div>
</body>
</html>
