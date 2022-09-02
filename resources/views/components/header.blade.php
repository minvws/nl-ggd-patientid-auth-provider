<header>
    <div>
        <img src="/logo.svg" alt="Logo GGD GHOR" width="84px" height="39.06px">
        <ul class="language">
            <a
                href="{{ url()->current() . '?lang=nl' }}"
                aria-current="{{ App::isLocale('nl') ? 'page' : 'false' }}"
                lang="nl"
            >NL <span>(deze pagina in het Nederlands)</span></a>
            <a
                href="{{ url()->current() . '?lang=en' }}"
                aria-current="{{ App::isLocale('en') ? 'page' : 'false' }}"
                lang="en"
            >EN <span>(this page in English)</span></a>
        </ul>
    </div>
</header>
