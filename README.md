# PHPMailer MVP (Ready-to-Use)

A minimal, clean starter to send emails with **PHPMailer**. Includes a Bootstrap 5 demo form, attachment support, CC, and SMTP config.

## Quick Start

1. **Download & extract** this zip.
2. Rename `config.php` and set your SMTP credentials (e.g., Gmail App Password, Mailtrap, Outlook).
3. Install dependencies:
   ```bash
   composer install
   ```
4. Serve the `public/` folder via PHP’s built-in server or your web server:
   ```bash
   php -S localhost:8000 -t public
   ```
5. Open http://localhost:8000 and send a test email.

## SMTP Tips

### Gmail (recommended: App Password)
- Go to Google Account → Security → 2-Step Verification → **App passwords**
- Create an app password for "Mail"
- Use:
  - Host: `smtp.gmail.com`
  - Port: `587`
  - Encryption: `tls`
  - Username: your full Gmail address
  - Password: the 16-char app password

### Mailtrap (for safe testing)
- Host: from your Mailtrap inbox settings
- Port: `587`
- Encryption: `tls`
- Username & Password: from Mailtrap

### Outlook / Office365
- Host: `smtp.office365.com`
- Port: `587`
- Encryption: `tls`

## File Structure

```
phpmailer-mvp/
├─ composer.json
├─ config.php
├─ public/
│  ├─ index.php
│  └─ send.php
└─ vendor/        # created by Composer
```

## Security Notes
- Never commit real passwords. Use environment variables in production.
- This demo escapes HTML and sets plain-text `AltBody`. Add server-side validation as needed.
