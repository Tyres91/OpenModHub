<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    public function imprint(): Response
    {
        return Inertia::render('Legal/Imprint', [
            'legalSettings' => $this->legalSettings(),
        ]);
    }

    public function privacy(): Response
    {
        return Inertia::render('Legal/Privacy', [
            'googleTagManagerId' => Setting::get('google_tag_manager_id', ''),
            'legalSettings' => $this->legalSettings(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function legalSettings(): array
    {
        return [
            'operator_name' => (string) Setting::get('legal_operator_name', ''),
            'represented_by' => (string) Setting::get('legal_represented_by', ''),
            'street' => (string) Setting::get('legal_street', ''),
            'postal_code' => (string) Setting::get('legal_postal_code', ''),
            'city' => (string) Setting::get('legal_city', ''),
            'country' => (string) Setting::get('legal_country', ''),
            'email' => (string) Setting::get('legal_email', ''),
            'phone' => (string) Setting::get('legal_phone', ''),
            'vat_id' => (string) Setting::get('legal_vat_id', ''),
            'privacy_contact' => (string) Setting::get('legal_privacy_contact', ''),
            'additional_info' => (string) Setting::get('legal_additional_info', ''),
        ];
    }
}
