# GGD PatientId Auth Provider

One of the main routes of user authentication for the CoronaCheck apps is [login via DigiD](providing-events-by-digid.md). For a significant amount of users DigiD is not available however, for example because a person does not have a BSN number. For negative tests, which lead to short-lived proofs, we typically use [retrieval codes](providing-events-by-token.md), but for longer-lived proofs such as vaccination certificates this isn't the most ideal authentication method. Therefor, this document describes a third alternative: authenticating users using a 'passwordless' approach directly via the event provider.

For providers who have already implemented the DigiD based route, adding this route is fairly easy, as long as the provider has the following data on hand:

* The unique Patient ID for a patient at the provider (in short the 'user identifier')
* The patient's full birth date
* A cellphone number of the patient
* If a cellphone number is not available, the e-mail address of the patient


## Development

`git clone `

`cp .env.example`

Change config variables.

`composer install`

`php artisan key:generate`

`npm install`

`npm run build`

Can be ran with internal webserver via `php artisan serve`

## Configuration variables

See [.env.example](.env.example)

Short list of some important variables.

```
APP_NAME=
APP_ENV=			                     # E.g. production
APP_KEY=                                 # Can be generated with `php artisan key:generate`
CODEGEN_HMAC_KEY=                        # HMAC key for patient hash generation function
CODEGEN_EXPIRY=900                       # Number of seconds before expiring authentication codes
SMS_GATEWAY_MESSAGEBIRD_API_KEY=         # Api key for message bird text messaging
OIDC_CLIENT_CONFIG_JSON=                 # Configuraiton file for oidc clients
```

## Keys and certs

Use `scripts/generate-certs.sh` and `scripts/generate-jwt-key.sh` to generate the localdev ssl cert and the JWT key, respectively.

## Installation production / acceptance

Environment variables via webserver or `.env` file.
Unpack release artifact, webroot should be the `public` folder within.

## References

* https://github.com/minvws/nl-covid19-coronacheck-provider-docs/blob/main/docs/providing-events-by-patient-id.md
* https://github.com/minvws/nl-covid19-coronacheck-app-coordination/blob/main/architecture/GGD%20PatientID%20Authentication%20Provider.md
