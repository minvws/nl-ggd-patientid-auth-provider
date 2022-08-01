* Settings *
Resource        page_objects/general.resource
Force Tags      CI


* Test Cases *
Test One
    Should Be True  1 == 1  # TODO
    New Browser     chromium    headless=${headless}
    New Page  localhost:445
    Take Screenshot   fullPage=True
