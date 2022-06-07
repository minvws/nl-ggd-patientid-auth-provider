<form method="POST" action="{{ $url }}?access_token={{ $access_token }}">
    {{ $slot }}
</form>
