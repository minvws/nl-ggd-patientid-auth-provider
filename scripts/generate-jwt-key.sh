#!/usr/bin/env bash

cd "$(dirname "$0")/../secrets"
openssl genrsa -out jwt.key 2048
