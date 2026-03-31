# SMTP Configuration Guide

Update `mail_config.php` with values from your email provider below.

## Gmail (with App Password)

1. Enable 2-factor authentication on your Gmail account.
2. Generate an **App Password**: https://myaccount.google.com/apppasswords
3. Use these values in `mail_config.php`:

```php
'smtp_enabled' => true,
'host' => 'smtp.gmail.com',
'username' => 'your-email@gmail.com',
'password' => 'xxxx xxxx xxxx xxxx',  // 16-char app password (remove spaces)
'port' => 587,
'secure' => 'tls',
'from_email' => 'your-email@gmail.com',
'from_name' => 'LUXE',
```

## Outlook / Microsoft 365

```php
'smtp_enabled' => true,
'host' => 'smtp.office365.com',
'username' => 'your-email@outlook.com',
'password' => 'your_password',
'port' => 587,
'secure' => 'tls',
'from_email' => 'your-email@outlook.com',
'from_name' => 'LUXE',
```

## SendGrid

1. Get API key from: https://app.sendgrid.com/settings/api_keys
2. Use `apikey` as username:

```php
'smtp_enabled' => true,
'host' => 'smtp.sendgrid.net',
'username' => 'apikey',
'password' => 'SG.your_api_key_here',
'port' => 587,
'secure' => 'tls',
'from_email' => 'verified-sender@yourdomain.com',  // must be verified in SendGrid
'from_name' => 'LUXE',
```

## Mailgun

1. Get SMTP credentials from: https://app.mailgun.com/app/account/security/api_keys
2. Use these values:

```php
'smtp_enabled' => true,
'host' => 'smtp.mailgun.org',
'username' => 'postmaster@yourdomain.mailgun.org',
'password' => 'your_mailgun_password',
'port' => 587,
'secure' => 'tls',
'from_email' => 'noreply@yourdomain.com',
'from_name' => 'LUXE',
```

## Local Testing (No Real Email)

If you want to test without sending real emails:

```php
'smtp_enabled' => false,  // falls back to PHP mail()
```

Or use a local mail trap like **Mailtrap.io**:

```php
'smtp_enabled' => true,
'host' => 'smtp.mailtrap.io',
'username' => 'your_mailtrap_inbox_username',
'password' => 'your_mailtrap_inbox_password',
'port' => 2525,
'secure' => 'tls',
'from_email' => 'noreply@luxe.local',
'from_name' => 'LUXE',
```

## Troubleshooting

- **Authentication failed**: double-check username and password.
- **Connection timeout**: verify host and port are correct.
- **Email sent but not arrived**: check spam folder or sender reputation (especially with free SMTP).
- **Enable debug mode** (optional): uncomment this in `register_process.php` to see PHPMailer errors:
  ```php
  $mail->SMTPDebug = 2;  // 0=off, 1=errors only, 2=commands+responses, 3=verbose
  ```

---

Once configured, test by registering a user via `LogIn.php` and check your inbox.
