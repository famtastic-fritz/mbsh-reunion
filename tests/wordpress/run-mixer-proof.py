#!/usr/bin/env python3
"""New isolated WordPress/WooCommerce DB; no provider credentials, external network or email."""
import os, pathlib, shutil, subprocess, sys, time, urllib.request, zipfile
R=pathlib.Path(__file__).resolve().parents[2]
cache=pathlib.Path.home()/'.cache/mbsh-mixer-wc-proof-20261016';cache.mkdir(parents=True,exist_ok=True)
vendor=pathlib.Path(os.environ.get('MBSH_WOOCOMMERCE_SOURCE',str(cache/'woocommerce')))
if not (vendor/'woocommerce.php').is_file():
 archive=cache/'woocommerce.11.0.1.zip'
 urllib.request.urlretrieve('https://downloads.wordpress.org/plugin/woocommerce.11.0.1.zip',archive)
 with zipfile.ZipFile(archive) as bundle:
  for member in bundle.infolist():
   target=(cache/member.filename).resolve()
   if not target.is_relative_to(cache.resolve()):raise RuntimeError('Invalid vendor archive path')
  bundle.extractall(cache)
assert 'Version: 11.0.1' in (vendor/'woocommerce.php').read_text()[:2000], 'Pinned WooCommerce 11.0.1 required'
(cache/'mail-sink.php').write_text("<?php\nadd_filter('pre_wp_mail',static fn()=>true,-1000);\nadd_filter('pre_http_request',static fn()=>new WP_Error('proof_network_disabled','External providers disabled in isolated proof.'),-1000);\n")
network='mbsh-mixer-proof-20261016';db='mbsh-mixer-wc-db-20261016';wp='mbsh-mixer-wc-app-20261016'
def run(*args):
 result=subprocess.run(args,text=True,capture_output=True)
 if result.returncode:raise RuntimeError(result.stderr[:2000] or result.stdout[:2000])
 return result.stdout.strip()
existing=run('docker','ps','-a','--format','{{.Names}}').splitlines()
if any(n in existing for n in [wp,db]):raise RuntimeError('A proof container with this name already exists; do not overwrite it')
if network in run('docker','network','ls','--format','{{.Name}}').splitlines():raise RuntimeError('Proof network already exists; do not overwrite it')
created=[];created_network=False
try:
 run('docker','network','create','--internal',network);created_network=True
 run('docker','run','-d','--name',db,'--network',network,'-e','MARIADB_ROOT_PASSWORD=isolated_mixer_test','-e','MARIADB_DATABASE=mixer_wp','-e','MARIADB_USER=mixer_wp','-e','MARIADB_PASSWORD=isolated_mixer_test','mariadb:11.4');created.append(db)
 for _ in range(60):
  try:run('docker','exec',db,'mariadb','-umixer_wp','-pisolated_mixer_test','mixer_wp','-e','SELECT 1');break
  except RuntimeError:time.sleep(.5)
 else:raise RuntimeError('Proof DB did not become ready')
 run('docker','run','-d','--name',wp,'--network',network,'-e','WORDPRESS_DB_HOST='+db,'-e','WORDPRESS_DB_NAME=mixer_wp','-e','WORDPRESS_DB_USER=mixer_wp','-e','WORDPRESS_DB_PASSWORD=isolated_mixer_test','-e',"WORDPRESS_CONFIG_EXTRA=define('WP_ENVIRONMENT_TYPE','local');define('DISABLE_WP_CRON',true);define('FAMTASTIC_REUNION_CHECKOUT_SECRET',str_repeat('t',64));",'-v',str(R/'wordpress/wp-content/plugins/famtastic-reunion-platform')+':/var/www/html/wp-content/plugins/famtastic-reunion-platform:ro','-v',str(vendor)+':/var/www/html/wp-content/plugins/woocommerce:ro','-v',str(cache/'mail-sink.php')+':/var/www/html/wp-content/mu-plugins/proof-mail-sink.php:ro','-v',str(R/'tests/wordpress')+':/proof:ro','wordpress:7.0.4-php8.3-apache');created.append(wp)
 for _ in range(60):
  try:run('docker','exec',wp,'test','-f','/var/www/html/wp-config.php');break
  except RuntimeError:time.sleep(.5)
 else:raise RuntimeError('WordPress files did not become ready')
 for mode in ['setup','catalog']:
  print(run('docker','exec',wp,'php','-d','memory_limit=512M','/proof/mixer-proof.php',mode))
 print(run(sys.executable,str(R/'tests/wordpress/mixer-ajax-proof.py')))
 print(run('docker','exec',wp,'php','-d','memory_limit=512M','/proof/mixer-proof.php','exercise'))
finally:
 for name in reversed(created):subprocess.run(['docker','rm','-f','-v',name],capture_output=True)
 if created_network:subprocess.run(['docker','network','rm',network],capture_output=True)
