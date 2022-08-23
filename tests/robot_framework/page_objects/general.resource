* Settings *
Library     Browser     jsextension=${CURDIR}/../lib/redirect.js
Library     Collections
Library     DebugLibrary
Library     OperatingSystem
Library     ${CURDIR}/../lib/jwt_library.py

* Keywords *
Suite Teardown Steps
    ${BROWSER_LOGS}     Close Page
    Close Browser
    Log         ${BROWSER_LOGS}
    ${LOG_FILE}  List Directory  storage/logs   *.log  True
    IF  ${LOG_FILE}
        ${LARAVEL_LOGS}     Get File    storage/logs/laravel.log
        Log     ${LARAVEL_LOGS}
        Copy File           storage/logs/laravel.log    ${OUTPUT DIR}
    END
