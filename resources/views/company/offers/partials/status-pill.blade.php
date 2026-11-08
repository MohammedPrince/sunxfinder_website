<span class="pill {{ ['active' => 'pill-green', 'expired' => 'pill-yellow', 'closed' => 'pill-purple'][$status] ?? 'pill-blue' }}">
    {{ __('company/messages.package_offers.status_' . $status) }}
</span>
