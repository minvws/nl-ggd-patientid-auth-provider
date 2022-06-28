#!/usr/bin/env bash

cd "$(dirname "$0")/../ssl"
openssl req -x509 -newkey rsa:4096 -nodes -keyout pap.localdev.key -out pap.localdev.crt -sha256 -days 3650 -subj '/CN=pap.localdev'
openssl req -x509 -newkey rsa:4096 -nodes -keyout pap-demo-client.localdev.key -out pap-demo-client.localdev.crt -sha256 -days 3650 -subj '/CN=pap-demo-client.localdev'
