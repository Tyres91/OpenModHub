<?php

namespace App\Notifications;

use App\Models\ModVersion;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ModVersionRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ModVersion $modVersion, public string $rejectionReason)
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

        $modUrl = route('mods.show', $this->modVersion->mod->slug);

        $data = [
            'user_name' => $notifiable->name,
            'mod_title' => $this->modVersion->mod->title,
            'mod_url' => $modUrl,
            'mod_slug' => $this->modVersion->mod->slug,
            'version' => $this->modVersion->version,
            'rejection_reason' => $this->rejectionReason,
            'reviewer_name' => $this->modVersion->reviewer?->name ?? '',
            'cta_text' => 'View Mod',
            'cta_url' => $modUrl,
            'site_name' => $service->getSiteName(),
            'site_url' => $service->getSiteUrl(),
        ];

        $subject = $service->getSubject('version_rejected', $locale);
        $body = $service->renderBody('version_rejected', $data, $locale);

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
