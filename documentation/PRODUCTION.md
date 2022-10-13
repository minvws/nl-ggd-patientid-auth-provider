# Production Use

1. Download the artifact produced by the Github workflow `rdo-package.yml`
2. Unzip the artifact, then un-tar-gz the resulting file.
3. Install files into desired location (ie: /var/www/ggd-patientid-auth-provider)
4. Set Apache/Nginx webroot to the `public` folder (ie: /var/www/ggd-patientid-auth-provider/public)
5. Configure `.env` and (optionally) use `.env.example` as a template. Please remember that all values must be set. A few tips for production use are:
    - `APP_DEBUG` must be `false`.
    - `APP_ENV` must be set to `production`.
    - `APP_KEY` must be generated and different from all other environments.
    - `LOG_LEVEL` should be set to `info`.
    - `CORS_ORIGINS` must include a comma separated list of the client's websites.
6. Configure certificates. See [CERTIFICATES.md](CERTIFICATES.md) for more information.
7. Configure the OIDC clients in clients.json. An example can be found in `clients.json.example`
8. You can test the application using the demo setup found in the development configuration.

