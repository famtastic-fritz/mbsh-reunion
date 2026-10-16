#!/usr/bin/env python3
"""Isolated MariaDB + PHP proof. No real customer records or external providers."""
import json, os, socket, subprocess, tempfile, time, urllib.request, urllib.error
from pathlib import Path
R=Path(__file__).resolve().parents[2]
name=f'mbsh-mixer-db-proof-{os.getpid()}'
def run(*args,input=None):
 return subprocess.run(args,input=input,text=True,capture_output=True,check=True).stdout.strip()
def sql(statement):
 return run('docker','exec','-i',name,'mariadb','-umbsh_test','-pmbsh_test_password','-N','-B','mbsh_portal_test',input=statement)
def request(route,body):
 data=json.dumps(body).encode();req=urllib.request.Request(f'http://127.0.0.1:{http_port}/{route}',data,{'Content-Type':'application/json'})
 try:
  with urllib.request.urlopen(req) as response:return response.status,json.load(response)
 except urllib.error.HTTPError as e:return e.code,json.load(e)
server=None
with tempfile.TemporaryDirectory(prefix='mbsh-mixer-proof-') as temp:
 try:
  run('docker','run','-d','--rm','--name',name,'-e','MARIADB_ROOT_PASSWORD=root_test_password','-e','MARIADB_DATABASE=mbsh_portal_test','-e','MARIADB_USER=mbsh_test','-e','MARIADB_PASSWORD=mbsh_test_password','-p','127.0.0.1::3306','mariadb:11.4')
  port=run('docker','port',name,'3306/tcp').rsplit(':',1)[1]
  for _ in range(60):
   try:sql('SELECT 1');break
   except subprocess.CalledProcessError:time.sleep(.5)
  else:raise RuntimeError('Database startup failed')
  sql((R/'tests/backend/fixtures/prerequisites.sql').read_text());sql((R/'backend/schema.sql').read_text())
  sql("ALTER TABLE ticket_orders MODIFY price_tier ENUM('early_bird','regular') NOT NULL; INSERT INTO ticket_orders(order_code,contact_name,email,quantity,unit_price,total_amount,price_tier) VALUES('MBSH-OLD001','Historic Fixture','history@example.test',1,185,185,'early_bird');")
  sql((R/'backend/migrations/20261016_mixer_offer.sql').read_text())
  assert sql("SELECT CONCAT(unit_price,':',price_tier) FROM ticket_orders WHERE order_code='MBSH-OLD001'")=='185.00:early_bird'
  with socket.socket() as sock:sock.bind(('127.0.0.1',0));http_port=sock.getsockname()[1]
  env=dict(os.environ,MBSH_CONFIG_PATH=str(R/'tests/backend/fixtures/mixer-config.php'),MBSH_TEST_DB_PORT=port,MBSH_TEST_HTTP_PORT=str(http_port))
  log=open(Path(temp)/'php.log','w')
  server=subprocess.Popen(['php','-S',f'127.0.0.1:{http_port}','-t',str(R/'backend')],env=env,stdout=log,stderr=log)
  for _ in range(30):
   try:
    with socket.create_connection(('127.0.0.1',http_port),timeout=.2):break
   except OSError:time.sleep(.1)
  payload={'contact_name':'Mixer Fixture','email':'fixture@example.test','quantity':2,'form_loaded_at':int(time.time()*1000)-5000}
  status,first=request('ticket-order.php',payload);assert status==200,first
  assert first['unit_price']==30 and first['total_amount']==60 and first['price_tier']=='mixer'
  status,retry=request('ticket-order.php',payload);assert status==200 and retry['replayed'] and retry['order_code']==first['order_code']
  assert sql("SELECT COUNT(*) FROM ticket_orders WHERE email='fixture@example.test'")=='1'
  status,tamper=request('ticket-order.php',dict(payload,quantity=1,unit_price=.01,total_amount=.01));assert status==200 and tamper['total_amount']==30
  status,invalid=request('ticket-order.php',dict(payload,quantity=11));assert status==400
  status,closed=request('menu.php',{});assert status==410 and closed['error']=='meal_selection_closed'
  assert sql("SELECT COUNT(*) FROM ticket_orders WHERE payment_status='paid'")=='0'
  assert sql('SELECT COUNT(*) FROM menu_selections')=='0'
  print('PASS mixer integration: additive migration preserves history; $30/$60 server totals; retry deduplication; client-price tampering; quantity limits; meal endpoint closed; no paid rows or meal writes')
 finally:
  if server:server.terminate();server.wait(timeout=5)
  subprocess.run(['docker','rm','-f',name],capture_output=True)
