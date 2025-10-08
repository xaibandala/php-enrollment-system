# PHPMailer MVP (Ready-to-Use)

A minimal, clean starter to send emails with **PHPMailer**. Includes a Bootstrap 5 demo form, attachment support, CC, and SMTP config.

USER SIDE
![enroll1](https://github.com/user-attachments/assets/3d44f0e5-267c-4a05-99ef-b102220cab11)

![enroll2](https://github.com/user-attachments/assets/9626441c-ceec-427d-9ed7-ed7e51ea882f)

![enroll3](https://github.com/user-attachments/assets/6c1a5ad7-73f8-4ca7-a088-afdc5265365f)

![enroll4](https://github.com/user-attachments/assets/b3eaa474-fcec-457e-82c1-15c428cbb541)

![enroll5](https://github.com/user-attachments/assets/46c1fba6-5511-4852-97d3-01fc697b948f)

ADMIN SIDE

![enrolladmin1](https://github.com/user-attachments/assets/08d53b3e-e488-4f5b-abe1-0ea73f27d304)

![enrolladmin2](https://github.com/user-attachments/assets/d066950e-d2fd-4665-b13f-d2a4859c6e6e)


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

admin 

admin
admin2025
