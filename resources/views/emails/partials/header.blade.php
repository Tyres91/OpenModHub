<table class="header" width="100%">
    <tr>
        <td>
            @php
                $logoUrl = app(\App\Services\EmailTemplateService::class)->getLogoUrl();
                $siteName = app(\App\Services\EmailTemplateService::class)->getSiteName();
                $showText = \App\Models\Setting::get('site_logo_show_text', '1') === '1';
            @endphp

            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}">
                <br>
            @endif

            @if($showText)
                <h1 class="header-text">{{ $siteName }}</h1>
            @endif
        </td>
    </tr>
</table>
