#!/usr/bin/env bash

cd "$(dirname "$0")/../secrets"
openssl req -x509 -nodes -newkey rsa:4096 -keyout jwt.key -out cert.pem -sha256 -days 365 -subj '/CN=pap.localdev'

