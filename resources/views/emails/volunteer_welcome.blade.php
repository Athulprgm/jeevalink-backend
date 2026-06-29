<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to JeevaLink</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap');

        body { 
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background-color: #f8f9fa; 
            margin: 0; 
            padding: 0; 
            -webkit-font-smoothing: antialiased;
            color: #333;
        }
        
        .wrapper {
            padding: 40px 20px;
            background-color: #f8f9fa;
        }

        .container { 
            max-width: 600px; 
            margin: 0 auto; 
            background: #ffffff; 
            border-radius: 16px; 
            overflow: hidden; 
            box-shadow: 0 10px 40px -10px rgba(220, 38, 38, 0.15); 
        }

        .header { 
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); 
            padding: 40px 30px; 
            text-align: center; 
            color: white; 
            position: relative;
        }
        
        /* Subtle decorative pattern in header */
        .header::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(white 1px, transparent 1px);
            background-size: 20px 20px;
            opacity: 0.1;
        }

        .header h1 { 
            margin: 0; 
            font-size: 32px; 
            font-weight: 800; 
            letter-spacing: -0.5px;
            position: relative;
            z-index: 1;
        }
        
        .header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            font-weight: 300;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .content { 
            padding: 40px 30px; 
            line-height: 1.7; 
        }

        h2 {
            color: #b91c1c;
            font-size: 24px;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 20px;
        }

        p {
            font-size: 16px;
            color: #4b5563;
            margin-bottom: 24px;
        }

        .credentials-box { 
            background: #fff5f5; 
            border-left: 4px solid #ef4444; 
            border-radius: 0 8px 8px 0; 
            padding: 24px; 
            margin: 30px 0; 
        }

        .credential-item {
            margin-bottom: 15px;
        }

        .credential-item:last-child {
            margin-bottom: 0;
        }

        .credential-label {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            color: #991b1b;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }

        .credential-value {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            background: white;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #fecaca;
            display: inline-block;
        }

        .button-container {
            text-align: center;
            margin: 40px 0;
        }

        .button { 
            display: inline-block; 
            padding: 14px 32px; 
            background: #ef4444; 
            color: #ffffff !important; 
            text-decoration: none; 
            border-radius: 50px; 
            font-weight: 600; 
            font-size: 16px;
            box-shadow: 0 4px 14px 0 rgba(239, 68, 68, 0.39);
            transition: all 0.3s ease;
        }
        
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
            background: #dc2626;
        }

        .security-note {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 16px;
            font-size: 14px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
        }
        
        .security-icon {
            font-size: 20px;
        }

        .footer { 
            padding: 24px 30px; 
            text-align: center; 
            font-size: 14px; 
            color: #9ca3af; 
            background-color: #f9fafb; 
            border-top: 1px solid #f3f4f6; 
        }
        
        .footer strong {
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>JeevaLink</h1>
                <p>Connecting Lifesavers</p>
            </div>
            
            <div class="content">
                <h2>Welcome to the Team, {{ $name }}!</h2>
                
                <p>You have been officially added as a Volunteer to JeevaLink by our administrator. We are absolutely thrilled to have you on board. Your dedication will help us streamline blood donations and save more lives.</p>
                
                <p>Your volunteer account has been created successfully. Below are your login credentials:</p>
                
                <div class="credentials-box">
                    <div class="credential-item">
                        <span class="credential-label">Email Address</span>
                        <span class="credential-value">{{ $email }}</span>
                    </div>
                    <div class="credential-item">
                        <span class="credential-label">Temporary Password</span>
                        <span class="credential-value">{{ $password }}</span>
                    </div>
                </div>
                
                <div class="button-container">
                    <a href="{{ $loginUrl }}" class="button">Log In to JeevaLink</a>
                </div>
                
                <div class="security-note">
                    <span class="security-icon">🔒</span>
                    <div>
                        <strong>Security Tip:</strong> For your security, we strongly recommend changing your password immediately after your first login.
                    </div>
                </div>
                
                <p style="margin-bottom: 0;">Thank you for joining our mission,<br><strong style="color: #b91c1c;">The JeevaLink Team</strong></p>
            </div>
            
            <div class="footer">
                &copy; {{ date('Y') }} <strong>JeevaLink</strong>. All rights reserved.<br>
                <span style="font-size: 12px; margin-top: 8px; display: inline-block;">This is an automated message. Please do not reply directly to this email.</span>
            </div>
        </div>
    </div>
</body>
</html>
