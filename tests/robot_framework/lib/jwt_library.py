import json
from datetime import datetime, timedelta, timezone

from jwt import (
    JWT,
    jwk_from_dict,
    jwk_from_pem,
)
from jwt.utils import get_int_from_datetime

class jwt_library():

    def jwt_decode(self):
        instance = JWT()

        message = {
            'iss': 'https://example.com/',
            'sub': 'yosida95',
            'iat': get_int_from_datetime(datetime.now(timezone.utc)),
            'exp': get_int_from_datetime(
                datetime.now(timezone.utc) + timedelta(hours=1)),
        }

        """
        Encode the message to JWT(JWS).
        """

        try:
            # Or load a RSA key from a PEM file.
            with open('secrets/cert.pem', 'rb') as fh:
                signing_key = jwk_from_pem(fh.read())
            # You can also load an octet key in the same manner as the RSA.
            # signing_key = jwk_from_dict({'kty': 'oct', 'k': '...'})

            compact_jws = instance.encode(message, signing_key, alg='RS256')

            """
            Decode the JWT with verifying the signature.
            """

            # Load a public key from PEM file corresponding to the signing private key.
            with open('secrets/jwt.key', 'r') as fh:
                verifying_key = jwk_from_dict(json.load(fh))

            message_received = instance.decode(
                compact_jws, verifying_key, do_time_check=True)

            return message_received
        except Exception as e:
            return e
