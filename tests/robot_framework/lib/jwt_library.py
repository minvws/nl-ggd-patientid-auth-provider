from cryptography.x509 import load_pem_x509_certificate
from cryptography.hazmat.backends import default_backend
import jwt

class jwt_library():

    def decode_jwt_token(self, jwt_token):
        # cert_str = str.encode(open("../../../secrets/cert.pem", "r").read())
        cert_str = str.encode(open("secrets/cert.pem", "r").read())

        cert_obj = load_pem_x509_certificate(cert_str, default_backend())
        public_key = cert_obj.public_key()

        decoded = jwt.decode(jwt_token, public_key, algorithms=["RS256"], audience="https://localhost:888/coronacheck/1.0")
        return decoded
