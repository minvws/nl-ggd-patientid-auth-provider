<form method="POST" action="{{ $url }}?access_token={{ $access_token }}" class="horizontal-view help">
    {{ $slot }}
</form>
