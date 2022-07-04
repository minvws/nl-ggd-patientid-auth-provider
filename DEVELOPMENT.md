# Installation & development

## Configuration

```sh
cp .env.example .env
cp clients.json.example clients.json
```

Edit `.env` to fill out the required environment variables.

For local development it can be useful to use the dummy providers for Yenlo, e-mail and SMS:

```
INFORETRIEVAL_SERVICE=dummy
EMAIL_SERVICE=dummy
SMS_SERVICE=dummy
```

The dummy SMS and e-mail providers output to the logs (`tail -f storage/logs/laravel.log`) and the info retrieval dummy has hardcoded user data (see [InfoRetrievalGateway/Dummy.php#L19-L20](https://github.com/minvws/ggd-patientid-auth-provider/blob/a21f7d46d9c13a8362d6bee7df50f55b022c1b4c/app/Services/InfoRetrievalGateway/Dummy.php#L19-L20)).

See [Configuration variables](./README.md#configuration-variables) in the README for a full list of environment variables.

Edit `clients.json` to reflect the `name` and `redirect_uris` of at least one client. The exmple from `client.json.examples` should work out of the box for the demo client app if you use docker-compose.

## Run with docker-compose

Local requirements: `docker`, `docker-compose`, `openssl`, `composer` and `npm`.

Use `scripts/generate-certs.sh` and `scripts/generate-jwt-key.sh` to generate the `localdev` SSL certs and the JWT key, respectively.

Install the dependencies and build the frontend:

```sh
composer install
php artisan key:generate`
npm install
npm run build
```

Add `pap.localdev` and `pap-demo-client.localdev` to your `/etc/hosts` file (or equivalent).

Then run the application via docker-compose:
```sh
docker-compose up
```

By default the provider can be accessed at [https://pap.localdev:444](https://pap.localdev:444) and the demo client app at [https://pap-demo-client.localdev:445](https://pap-demo-client.localdev:445). These ports can be configured via the `APP_PORT` and `DEMO_CLIENT_PORT` env vars.

## Run another way (e.g. `php artisan serve`)

Local requirements: PHP 8 with `ext-json` and `ext-sodium`, `composer` and `npm`.

Install the dependencies and build the frontend:

```sh
composer install
php artisan key:generate`
npm install
npm run build
```

Add `pap.localdev` to your `/etc/hosts` file (or equivalent).

Then run the application however you normally run PHP application, or with artisan:

```
php artisan serve
```
