* Settings *
Resource    page_objects/general.resource
Force Tags  CI


* Test Cases *
Test One
    New Browser     chromium    headless=${headless}  args=['--allow-insecure-localhost']
    New Page  https://localhost:445
    Take Screenshot   fullPage=True
