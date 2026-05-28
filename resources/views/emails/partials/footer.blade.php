<table class="footer" style="width: 100%;">
    <tr>
        <td>
            @php
                $service = app(\App\Services\EmailTemplateService::class);
                $legal = $service->getLegalInfo();
                $siteName = $service->getSiteName();
                $siteUrl = $service->getSiteUrl();
            @endphp

            @if(filled($legal['operator']))
                <p><strong>{{ $legal['operator'] }}</strong></p>
            @endif

            @if(filled($legal['street']) || filled($legal['postal_code']) || filled($legal['city']) || filled($legal['country']))
                <p>
                    {{ implode(', ', array_filter([$legal['street'], $legal['postal_code'] . ' ' . $legal['city'], $legal['country']])) }}
                </p>
            @endif

            @if(filled($legal['email']))
                <p><a href="mailto:{{ $legal['email'] }}">{{ $legal['email'] }}</a></p>
            @endif

            @if(filled($legal['phone']))
                <p>{{ $legal['phone'] }}</p>
            @endif

            <p>&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
            <p><a href="{{ $siteUrl }}">{{ $siteUrl }}</a></p>

            @if($show_unsubscribe && isset($unsubscribe_url))
                <p style="margin-top: 12px;">
                    <a href="{{ $unsubscribe_url }}">Unsubscribe from these notifications</a>
                </p>
            @endif
        </td>
    </tr>
</table>
