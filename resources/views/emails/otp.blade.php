<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your OTP Code</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; margin: 0;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="600"
        style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        <tr>
            <td align="center" style="padding: 40px 20px; background-color: #2563eb; color: white;">
                <h1 style="margin: 0; font-size: 28px;">Puck Recruiter</h1>
            </td>
        </tr>
        <tr>
            <td style="padding: 40px 30px; text-align: center;">
                <h2 style="color: #1f2937;">Your One-Time Login Code</h2>
                <p style="font-size: 16px; color: #4b5563; margin: 20px 0;">
                    Use the code below to complete your login.
                </p>
                <div style="margin: 30px 0;">
                    <span
                        style="font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #2563eb; background: #f0f7ff; padding: 15px 30px; border-radius: 8px; display: inline-block;">
                        {{ $otp }}
                    </span>
                </div>
                <p style="color: #6b7280; margin: 20px 0;">
                    This code will expire in <strong>10 minutes</strong>.
                </p>
                <p style="color: #9ca3af; font-size: 14px;">
                    If you didn't request this, please ignore this email.
                </p>
            </td>
        </tr>
        <tr>
            <td style="background-color: #f8fafc; padding: 20px; text-align: center; color: #94a3b8; font-size: 12px;">
                &copy; {{ date('Y') }} Puck Recruiter. All rights reserved.
            </td>
        </tr>
    </table>
</body>

</html>
