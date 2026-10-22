#!/usr/bin/env python3
"""Install only the reunion certificate after the existing ACME job renews it.

Lives outside the webroot. The API token is provisioned separately, never in Git.
Use HTTPS UAPI because the shared-host jailed shell cannot run the local CLI.
"""
import json
import ssl
import urllib.parse
import urllib.request
from pathlib import Path

DIRECTORY = Path('/home/nineoo/.config/mbsh-tls')
DOMAIN = 'mbsh96reunion.com'


def main():
    files = {name: DIRECTORY / name for name in
             ('cert.pem', 'chain.pem', 'privkey.pem', 'cpanel-token')}
    for path in files.values():
        if not path.is_file() or path.is_symlink():
            raise RuntimeError('Required private TLS file is absent or a symlink')
        path.chmod(0o600)
    cert = ssl._ssl._test_decode_cert(str(files['cert.pem']))
    names = {value for kind, value in cert['subjectAltName'] if kind == 'DNS'}
    if names != {DOMAIN, 'www.' + DOMAIN}:
        raise RuntimeError('Certificate names do not match the reunion hosts')
    payload = urllib.parse.urlencode({
        'domain': DOMAIN,
        'cert': files['cert.pem'].read_text(),
        'key': files['privkey.pem'].read_text(),
        'cabundle': files['chain.pem'].read_text(),
    }).encode()
    request = urllib.request.Request(
        'https://famtasticinc.com:2083/execute/SSL/install_ssl', data=payload,
        headers={'Authorization': 'cpanel nineoo:' + files['cpanel-token'].read_text().strip()},
    )
    with urllib.request.urlopen(request, timeout=45) as response:
        result = json.load(response)
    if result.get('status') != 1:
        raise RuntimeError('cPanel did not confirm certificate installation')
    print(json.dumps({'installed': True, 'domain': DOMAIN, 'expires': cert['notAfter']}))


if __name__ == '__main__':
    try:
        main()
    except Exception as error:
        # API responses and request headers can contain secrets. Log type only.
        raise SystemExit('MBSH TLS installation failed: ' + type(error).__name__)
