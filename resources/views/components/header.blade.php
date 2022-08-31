<header>
    <div>
        <img src="/logo.svg" alt="Logo GGD GHOR" width="84px" height="39.06px">
        <div class="language">
            <a
                href="{{ url()->current() . '?lang=nl' }}"
                aria-current="{{ App::isLocale('nl') ? 'page' : 'false' }}"
            >NL</a>
            <a
                href="{{ url()->current() . '?lang=en' }}"
                aria-current="{{ App::isLocale('en') ? 'page' : 'false' }}"
            >EN</a>
        </div>
    </div>
</header>
