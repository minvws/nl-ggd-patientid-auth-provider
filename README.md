# GGD PatientID Auth Provider

GGD PatientID Auth Provider (or GGD-PAP for short) is an OAuth 2.0 [OIDC](https://openid.net/connect/) + [PKCE](https://tools.ietf.org/html/rfc7636) authentication provider for the alternative authentication route for users who can not use DigiD, as described in the provider docs ([Providing Vaccination / Test / Recovery / Assessment Events by Patient ID](https://github.com/minvws/nl-covid19-coronacheck-provider-docs/blob/main/docs/providing-events-by-patient-id.md)) and in the app coordination docs ([GGD PatientID Authentication Provider](https://github.com/minvws/nl-covid19-coronacheck-app-coordination/blob/main/architecture/GGD%20PatientID%20Authentication%20Provider.md)).

## Integrating in an app

If you are familiar with OIDC and PKCE this should be fairly straightforward. The authorization endpoint is `/oidc/authorize` and the token endpoint is `/oidc/accesstoken`. Only `response_type=code` with `scope=openid` is supported. PKCE is mandatory and only `code_challenge_method=S256` is supported.

For full integration instructions, see [INTEGRATING.md](./INTEGRATING.md).

## Installation & development

For local installation instructions, see [DEVELOPMENT.md](./DEVELOPMENT.md).

## Demo client app

If you followed the instruction in [DEVELOPMENT.md](./DEVELOPMENT.md) for running the app with docker-compose, you can access the demo client app at [https://pap-demo-client.localdev:445](https://pap-demo-client.localdev:445).

Otherwise, host the `./demo-client` directory somewhere with a simple static http server.

To use the demo client app, the PAP app needs to have it configured in its `clients.json`.

## Production / acceptance

Generate a JWT key & cert (replace `fqdn.example.com` with the FQDN of the authentication provider):

```
openssl req -x509 -nodes -newkey rsa:4096 -keyout jwt.key -out cert.pem -sha256 -days 365 -subj '/CN=fqdn.example.com'
```

This generates a `jwt.key` and a `cert.pem` file, note these have a 1 year validity. 

Environment variables can be configured however you like, e.g. via the webserver or `.env` file.

Unpack release artifact, webroot should be the `public` folder within.

## Configuration variables

See [.env.example](./.env.example).

Short list of some important variables.

```
APP_NAME=
APP_ENV=			                     # E.g. production
APP_KEY=                                 # Can be generated with `php artisan key:generate`
CODEGEN_HMAC_KEY=                        # HMAC key for patient hash generation function
CODEGEN_EXPIRY=900                       # Number of seconds before expiring authentication codes
SMS_GATEWAY_MESSAGEBIRD_API_KEY=         # Api key for message bird text messaging
OIDC_CLIENT_CONFIG_JSON=                 # Configuration file for oidc clients
CMS_X509_CERT=                           # Certificate for verifying Yenlo CMS signatures
CMS_X509_CHAIN=                          # Accompanying CA chain
```

