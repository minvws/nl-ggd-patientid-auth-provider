* Settings *
Resource    page_objects/general.resource
Force Tags  CI

* Variables *
${START_AUTH}       https://localhost:444/oidc/authorize?response_type=code&client_id=client-123&state=a&scope=openid&redirect_uri=https%3A%2F%2Flocalhost%3A445%2Fcallback.html&code_challenge=oAh_MAHECKHJUmXa9iWFWSybV45sm-pUQPSG5-BB_xc&code_challenge_method=S256
${CODE_CHALLANGE}   oAh_MAHECKHJUmXa9iWFWSybV45sm-pUQPSG5-BB_xc
${CODE_VERIFIER}    asdfghjkloasdfghjkloasdfghjkloasdfghjklogtyh

* Test Cases *
Test One
    ${RESULT}  JWT Decode
    Log    ${RESULT}   console=True
    New Browser     chromium    headless=${headless}  args=['--allow-insecure-localhost']
    New Page        https://localhost:445
    Go To     https://localhost:444/oidc/authorize?response_type=code&client_id=client-123&state=a&scope=openid&redirect_uri=https%3A%2F%2Flocalhost%3A445%2Fcallback.html&code_challenge=oAh_MAHECKHJUmXa9iWFWSybV45sm-pUQPSG5-BB_xc&code_challenge_method=S256
    # code challange bewaren: oAh_MAHECKHJUmXa9iWFWSybV45sm-pUQPSG5-BB_xc
    # url geeft 302 terug, haal daar de location uit (header)
    # alleen code= eruit vissen

    Go To       ${start_auth}
    Fill Text   id=patient_id   12345678
    Fill Text   id=birthdate    1976-10-16
    Click       " Verder "


    ${LARAVEL_LOGS}     Get File        ${EXECDIR}/storage/logs/laravel.log
    ${SMS_CODE}         Set Variable    ${LARAVEL_LOGS.split("\n")[-2].split()[-1]}
    Fill Text           id=code         ${SMS_CODE}
    ${RESPONSE_HEADERS}  Click And Get Response Headers     //button    verify  
    ${LOCATION_CODE}    Set Variable    ${RESPONSE_HEADERS["location"].split("code=")[-1]}

    Go To           https://localhost:444
    ${RESPONSE}     HTTP    /oidc/accesstoken?grant_type=authorization_code&client_id=client-123&redirect_uri=https%3A%2F%2Flocalhost%3A445%2Fcallback.html&code_verifier=asdfghjkloasdfghjkloasdfghjkloasdfghjklogtyh&code=${LOCATION_CODE}  POST

    # debug
    # ${DECODED_JWT}  Decode JWT  ${RESPONSE["body"]["access_token"]}
    # SMS
    # Sleep  1h


# php artisan create:hash 12345678 1976-10-16