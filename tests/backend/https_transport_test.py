#!/usr/bin/env python3
"""Exercise the real .htaccess under disposable Apache; no external providers."""
import json
import os
import subprocess
import tempfile
import time
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
NAME = f'mbsh-https-proof-{os.getpid()}'
IMAGE = 'wordpress:7.0-php8.3-apache'


def run(*args):
    result = subprocess.run(args, capture_output=True, text=True)
    if result.returncode:
        raise RuntimeError(result.stderr.strip())
    return result.stdout


with tempfile.TemporaryDirectory(prefix='mbsh-https-proof-') as directory:
    base = Path(directory)
    webroot = base / 'webroot'
    webroot.mkdir()
    (webroot / '.htaccess').write_bytes((ROOT / 'frontend/.htaccess').read_bytes())
    (webroot / 'index.html').write_text('canonical fixture')
    (webroot / 'tickets.html').write_text('ticket fixture')
    (webroot / 'famtasticinc-landing').mkdir()
    (webroot / 'famtasticinc-landing/index.html').write_text('separate host fixture')
    challenge = webroot / '.well-known/acme-challenge'
    challenge.mkdir(parents=True)
    (challenge / 'proof').write_text('challenge fixture')
    (webroot / 'cors.php').write_bytes((ROOT / 'backend/lib/cors.php').read_bytes())
    (webroot / 'ticket-order.php').write_text('''<?php
require __DIR__.'/cors.php';
fam_cors(['allowed_origins'=>['https://mbsh96reunion.com','https://www.mbsh96reunion.com']]);
header('Content-Type: application/json');
echo json_encode(['method'=>$_SERVER['REQUEST_METHOD'],'body'=>file_get_contents('php://input')]);
''')
    run('openssl', 'req', '-x509', '-newkey', 'rsa:2048', '-nodes', '-days', '1',
        '-subj', '/CN=localhost', '-keyout', str(base / 'key.pem'), '-out', str(base / 'cert.pem'))
    (base / 'apache.conf').write_text('''ServerName localhost
<Directory /var/www/html>
  AllowOverride All
  Require all granted
</Directory>
<VirtualHost *:80>
  DocumentRoot /var/www/html
</VirtualHost>
<VirtualHost *:443>
  DocumentRoot /var/www/html
  SSLEngine on
  SSLCertificateFile /proof/cert.pem
  SSLCertificateKeyFile /proof/key.pem
</VirtualHost>
''')
    try:
        run('docker', 'create', '--name', NAME, '--network', 'none',
            '--entrypoint', 'bash', IMAGE, '-c',
            'cp /proof/apache.conf /etc/apache2/sites-enabled/000-default.conf && '
            'a2enmod rewrite headers ssl >/dev/null && exec apache2-foreground')
        # Copy fixtures so Docker contexts need no host-specific bind mounts.
        run('docker', 'cp', str(base) + '/.', NAME + ':/proof')
        run('docker', 'cp', str(webroot) + '/.', NAME + ':/var/www/html')
        run('docker', 'start', NAME)

        def request(url, origin=None, body=None):
            # Self-signed TLS exists only in this network-isolated fixture.
            args = ['docker', 'exec', NAME, 'curl', '-skS', '--max-time', '5',
                    '--resolve', 'mbsh96reunion.com:80:127.0.0.1',
                    '--resolve', 'mbsh96reunion.com:443:127.0.0.1',
                    '--resolve', 'www.mbsh96reunion.com:80:127.0.0.1',
                    '--resolve', 'www.mbsh96reunion.com:443:127.0.0.1',
                    '--resolve', 'famtasticinc.com:80:127.0.0.1',
                    '--resolve', 'famtasticinc.com:443:127.0.0.1',
                    '--resolve', 'unrelated.example:80:127.0.0.1', '-D', '-', url]
            if origin:
                args += ['-H', 'Origin: ' + origin]
            if body is not None:
                args += ['-H', 'Content-Type: application/json', '--data', body]
            response = run(*args)
            headers, content = response.split('\n\n', 1)
            status = int(headers.split()[1])
            location = next((line[10:] for line in headers.splitlines()
                             if line.lower().startswith('location: ')), None)
            return status, location, content

        for _ in range(50):
            try:
                request('https://mbsh96reunion.com/')
                break
            except RuntimeError:
                time.sleep(.1)
        else:
            raise RuntimeError('Apache fixture did not start: ' + run('docker', 'logs', NAME))

        for host in ['mbsh96reunion.com', 'www.mbsh96reunion.com']:
            status, location, _ = request(f'http://{host}/tickets.html?offer=updated&quantity=2')
            assert (status, location) == (308, 'https://mbsh96reunion.com/tickets.html?offer=updated&quantity=2')
            assert request(f'http://{host}/.well-known/acme-challenge/proof')[2] == 'challenge fixture'
        assert request('https://www.mbsh96reunion.com/tickets.html')[:2] == (308, 'https://mbsh96reunion.com/tickets.html')
        assert request('https://mbsh96reunion.com/tickets.html')[0] == 200
        assert request('http://unrelated.example/tickets.html')[0] == 200
        assert request('https://famtasticinc.com/')[2] == 'separate host fixture'
        assert request('http://famtasticinc.com/')[1] == 'https://famtasticinc.com/'
        assert request('http://mbsh96reunion.com/ticket-order.php', 'http://mbsh96reunion.com', '{"quantity":2}')[:2] == (308, 'https://mbsh96reunion.com/ticket-order.php')
        for _ in range(2):
            status, _, body = request('https://mbsh96reunion.com/ticket-order.php', 'https://mbsh96reunion.com', '{"quantity":2}')
            assert status == 200 and json.loads(body) == {'method': 'POST', 'body': '{"quantity":2}'}
        status, _, body = request('https://mbsh96reunion.com/ticket-order.php', 'https://untrusted.example', '{}')
        assert status == 403 and json.loads(body)['error'] == 'origin_not_allowed'
        print('PASS Apache HTTPS transport: apex/www redirects; query retention; no canonical loop; HTTP-01; shared-host isolation; HTTPS POST/retry; foreign-origin denial')
    finally:
        subprocess.run(['docker', 'rm', '-f', NAME], capture_output=True)
