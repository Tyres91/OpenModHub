<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        EmailTemplate::updateOrCreate(
            ['key' => EmailTemplate::KEY_VERIFY_EMAIL],
            [
                'subject_en' => 'Verify your email address',
                'subject_de' => 'Bestätige deine E-Mail-Adresse',
                'body_en' => "Hello {user_name},\n\nwelcome to {site_name}! Please verify your email address to get started.\n\nClick the button below to complete your registration.",
                'body_de' => "Hallo {user_name},\n\nwillkommen bei {site_name}! Bitte bestätige deine E-Mail-Adresse, um loszulegen.\n\nKlicke auf den Button unten, um deine Registrierung abzuschließen.",
                'is_active' => true,
            ],
        );

        EmailTemplate::updateOrCreate(
            ['key' => EmailTemplate::KEY_MOD_APPROVED],
            [
                'subject_en' => 'Your mod has been approved',
                'subject_de' => 'Deine Mod wurde genehmigt',
                'body_en' => "Hello {user_name},\n\ngreat news! Your mod \"{mod_title}\" has been approved and is now publicly visible on {site_name}.\n\nThank you for your contribution!",
                'body_de' => "Hallo {user_name},\n\ngute Neuigkeiten! Deine Mod \"{mod_title}\" wurde genehmigt und ist jetzt öffentlich auf {site_name} sichtbar.\n\nVielen Dank für deinen Beitrag!",
                'is_active' => true,
            ],
        );

        EmailTemplate::updateOrCreate(
            ['key' => EmailTemplate::KEY_MOD_REJECTED],
            [
                'subject_en' => 'Your mod was rejected',
                'subject_de' => 'Deine Mod wurde abgelehnt',
                'body_en' => "Hello {user_name},\n\nunfortunately your mod \"{mod_title}\" was rejected.\n\nReason: {rejection_reason}\n\nYou can revise your mod and resubmit it for review.",
                'body_de' => "Hallo {user_name},\n\nleider wurde deine Mod \"{mod_title}\" abgelehnt.\n\nBegründung: {rejection_reason}\n\nDu kannst deine Mod überarbeiten und erneut einreichen.",
                'is_active' => true,
            ],
        );

        EmailTemplate::updateOrCreate(
            ['key' => EmailTemplate::KEY_VERSION_APPROVED],
            [
                'subject_en' => 'New version approved',
                'subject_de' => 'Neue Version genehmigt',
                'body_en' => "Hello {user_name},\n\nthe new version \"{version}\" of your mod \"{mod_title}\" has been approved and is now available for download.",
                'body_de' => "Hallo {user_name},\n\ndie neue Version \"{version}\" deiner Mod \"{mod_title}\" wurde genehmigt und ist jetzt zum Download verfügbar.",
                'is_active' => true,
            ],
        );

        EmailTemplate::updateOrCreate(
            ['key' => EmailTemplate::KEY_VERSION_REJECTED],
            [
                'subject_en' => 'New version rejected',
                'subject_de' => 'Neue Version abgelehnt',
                'body_en' => "Hello {user_name},\n\nthe new version \"{version}\" of your mod \"{mod_title}\" was rejected.\n\nReason: {rejection_reason}\n\nYou can revise the version and resubmit it for review.",
                'body_de' => "Hallo {user_name},\n\ndie neue Version \"{version}\" deiner Mod \"{mod_title}\" wurde abgelehnt.\n\nBegründung: {rejection_reason}\n\nDu kannst die Version überarbeiten und erneut einreichen.",
                'is_active' => true,
            ],
        );
    }
}
