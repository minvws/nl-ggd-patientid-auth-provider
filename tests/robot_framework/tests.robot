* Settings *
Resource    page_objects/general.resource
Force Tags  CI

* Variables *
${START_AUTH}       https://localhost:444/oidc/authorize?response_type=code&client_id=client-123&state=a&scope=openid&redirect_uri=https%3A%2F%2Flocalhost%3A445%2Fcallback.html&code_challenge=oAh_MAHECKHJUmXa9iWFWSybV45sm-pUQPSG5-BB_xc&code_challenge_method=S256
${USER_HASH}        96fd493d72041b6b8794d8a3a7b60e39999b76bac06694aaaffa414af5a1158a

* Test Cases *
Test One
    New Browser     chromium    headless=${headless}  args=['--allow-insecure-localhost']
    New Page        https://localhost:445
    Go To     https://localhost:444/oidc/authorize?response_type=code&client_id=client-123&state=a&scope=openid&redirect_uri=https%3A%2F%2Flocalhost%3A445%2Fcallback.html&code_challenge=oAh_MAHECKHJUmXa9iWFWSybV45sm-pUQPSG5-BB_xc&code_challenge_method=S256

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

    ${RESULT}  Decode JWT Token     ${RESPONSE["body"]["access_token"]}

    Dictionary Should Contain Key       ${RESULT}  userHash     No userhash found in JWT token! Full decoded JWT: ${RESULT}
    Should Be Equal    ${USER_HASH}     ${RESULT["userHash"]}   UserHash does not match expected hash! Full decoded JWT: ${RESULT}
