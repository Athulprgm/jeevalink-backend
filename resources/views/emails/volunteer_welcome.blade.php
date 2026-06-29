<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to JeevaLink</title>
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header { background-color: #dc2626; padding: 30px 20px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
        .content { padding: 30px; color: #374151; line-height: 1.6; }
        .credentials-box { background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 20px; margin: 20px 0; }
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
            <h2>Welcome to the Team, {{ $name }}!</h2>
            <p>You have been added as a Volunteer to JeevaLink by an administrator. We are thrilled to have you on board to help save lives.</p>
            
            <p>Your account has been created successfully. Here are your login credentials:</p>
            
            <div class="credentials-box">
                <p style="margin-top: 0;"><strong>Email:</strong> {{ $email }}</p>
                <p style="margin-bottom: 0;"><strong>Password:</strong> {{ $password }}</p>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $loginUrl }}" class="button">Log In to JeevaLink</a>
            </div>
            
            <p>For security reasons, we strongly recommend changing your password after your first login.</p>
            
            <p>Thank you for joining our mission,<br>The JeevaLink Team</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} JeevaLink. All rights reserved.
        </div>
    </div>
</body>
</html>
