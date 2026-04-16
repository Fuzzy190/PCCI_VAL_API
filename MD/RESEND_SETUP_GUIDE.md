# Resend Email Integration Setup Guide

## Overview
Your Laravel application has been configured to use **Resend** as the email provider, replacing Gmail SMTP.

## Configuration Files Updated

### 1. `.env` File
```dotenv
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=onboarding@resend.dev
MAIL_FROM_NAME="PCCI_APP"
RESEND_API_KEY=re_J21yg2px_JqdDuNV5UH9jKghyrKxY7Q7A
```

### 2. `config/mail.php`
Added Resend mailer configuration:
```php
'resend' => [
    'transport' => 'resend',
],
```

### 3. `config/services.php`
Added Resend service configuration:
```php
'resend' => [
    'key' => env('RESEND_API_KEY'),
],
```

### 4. `app/Providers/AppServiceProvider.php`
Registered the custom Resend mail transport.

## Created Files

### `app/Services/ResendService.php`
Main service class that handles email sending via Resend API.

**Methods:**
- `sendMail()` - Send email with text and optional HTML
- `sendHtmlMail()` - Send email with HTML content

### `app/Mail/ResendTransport.php`
Custom Symfony mail transport for Laravel's Mail facade.

## Usage Examples

### 1. Using Laravel Mail Facade
```php
use Illuminate\Support\Facades\Mail;

Mail::raw('This is a test email', function ($message) {
    $message->to('recipient@example.com')
            ->subject('Test Subject');
});
```

### 2. Using a Mailable Class
```php
use Illuminate\Mail\Mailable;

class TestEmail extends Mailable
{
    public function build()
    {
        return $this->view('email-template')
                    ->subject('Test Subject');
    }
}

// Send it:
Mail::send(new TestEmail());
```

### 3. Using Notifications
```php
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentConfirmation extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Payment Confirmed')
                    ->greeting('Hello!')
                    ->line('Your payment has been received.')
                    ->button('View Receipt', url('/'));
    }
}

// Send it:
$user->notify(new PaymentConfirmation());
```

### 4. Using ResendService Directly
```php
use App\Services\ResendService;

$resendService = new ResendService();

// Send text email
$resendService->sendMail(
    toEmail: 'user@example.com',
    toName: 'User Name',
    subject: 'OTP Verification',
    text: 'Your OTP is: 123456'
);

// Send HTML email
$resendService->sendHtmlMail(
    toEmail: 'user@example.com',
    subject: 'OTP Verification',
    html: '<p>Your OTP is: <strong>123456</strong></p>'
);
```

## Sending OTP Emails

### Example: OTP Notification Class
```php
<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OtpNotification extends Notification
{
    protected string $otp;

    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Your One-Time Password (OTP)')
                    ->greeting('Hello!')
                    ->line('Your OTP code is:')
                    ->line($this->otp)
                    ->line('This code expires in 10 minutes.')
                    ->line('Do not share this code with anyone.');
    }
}
```

### Usage:
```php
use App\Notifications\OtpNotification;

// Send OTP to user
$user->notify(new OtpNotification('123456'));
```

## Troubleshooting

### 1. Check if Resend Package is Installed
```bash
composer show | grep resend
```

### 2. If Resend Package Not Found
```bash
composer require resend/resend-php
```

### 3. Clear Cache After Configuration Changes
```bash
php artisan config:cache
```

### 4. Test Email Sending
```bash
php artisan tinker

# Test with ResendService
app('App\Services\ResendService')->sendMail(
    'test@example.com',
    'Test User',
    'Test Subject',
    'This is a test email'
);

# Test with Mail facade
Mail::raw('Test email', function ($m) {
    $m->to('test@example.com')->subject('Test');
});
```

## Important Notes

1. **Update `from` Email**: The current `MAIL_FROM_ADDRESS` is set to `onboarding@resend.dev` (Resend's test domain). Before going to production, update this to your verified domain in Resend.

2. **Verify Domain**: In Resend dashboard, verify your domain and update the `MAIL_FROM_ADDRESS` in `.env`.

3. **API Key Security**: Your API key is in the `.env` file. Never commit this to version control without proper secrets management.

4. **Rate Limits**: Resend has rate limits based on your plan. Check your Resend dashboard for current limits.

## Next Steps

1. Verify your domain in the Resend dashboard
2. Update `MAIL_FROM_ADDRESS` with your verified domain
3. Test sending emails using the examples above
4. Update all existing notification classes to use the new mail driver
5. Remove old Gmail credentials from `.env` (optional, but recommended)

## Support

- Resend Documentation: https://resend.com/docs
- Laravel Mail Documentation: https://laravel.com/docs/mail
