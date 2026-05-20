<?php

namespace App\Notifications;

use App\Models\Mod;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ModRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Mod $mod, public string $rejectionReason)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $service = app(EmailTemplateService::class);
        $locale = $notifiable->locale ?? 'en';

        $modUrl = route('mods.show', $this->mod->slug);

        $data = [
            'user_name' => $notifiable->name,
            'mod_title' => $this->mod->title,
            'mod_url' => $modUrl,
            'mod_slug' => $this->mod->slug,
            'rejection_reason' => $this->rejectionReason,
            'reviewer_name' => $this->mod->reviewer?->name ?? '',
            'cta_text' => 'View Mod',
            'cta_url' => $modUrl,
            'site_name' => $service->getSiteName(),
            'site_url' => $service->getSiteUrl(),
        ];

        $subject = $service->getSubject('mod_rejected', $locale);
        $body = $service->renderBody('mod_rejected', $data, $locale);

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.layouts.notification', [
                'locale' => $locale,
                'subject' => $subject,
                'body' => $body,
                'cta_url' => $modUrl,
                'cta_text' => 'View Mod',
                'show_unsubscribe' => true,
            ])
            ->text(strip_tags($body) . "\n\n" . 'View Mod: ' . $modUrl);
    }
}
