#!/usr/bin/env bash

openssl req -x509 -newkey rsa:4096 -nodes -keyout pap.localdev.key -out pap.localdev.crt -sha256 -days 3650 -subj '/CN=pap.localdev'
