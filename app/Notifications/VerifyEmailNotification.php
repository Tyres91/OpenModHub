<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $service = app(EmailTemplateService::class);
        $locale = $notifiable->locale ?? 'en';

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );

        $data = [
            'user_name' => $notifiable->name,
            'verification_url' => $verificationUrl,
            'cta_text' => 'Verify Email',
            'cta_url' => $verificationUrl,
            'site_name' => $service->getSiteName(),
            'site_url' => $service->getSiteUrl(),
        ];

        $subject = $service->getSubject('verify_email', $locale);
        $body = $service->renderBody('verify_email', $data, $locale);

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.layouts.notification', [
                'locale' => $locale,
                'subject' => $subject,
                'body' => $body,
                'cta_url' => $verificationUrl,
                'cta_text' => 'Verify Email',
                'show_unsubscribe' => false,
            ]);
    }
}
