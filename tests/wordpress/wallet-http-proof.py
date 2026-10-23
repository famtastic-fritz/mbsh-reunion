#!/usr/bin/env python3
import json,subprocess,urllib.parse
APP='mbsh-mixer-wc-app-20261016'
def php(*args):return subprocess.run(['docker','exec',APP,'php','-d','memory_limit=512M','/proof/wallet-proof.php',*args],text=True,capture_output=True,check=True).stdout.strip()
def http(who,path,body=None,csrf=None):
 args=['docker','exec','-i',APP,'curl','-sS','-b','/tmp/wallet-'+who+'.cookies','-c','/tmp/wallet-'+who+'.cookies','-w','\n%{http_code}']
 if body is not None:args+=['-H','Content-Type: application/json','--data-binary','@-']
 if csrf:args+=['-H','X-CSRF-Token: '+csrf]
 r=subprocess.run(args+['http://127.0.0.1/portal/'+path],input=json.dumps(body) if body is not None else None,text=True,capture_output=True,check=True).stdout
 data,status=r.rsplit('\n',1)
 try:return int(status),json.loads(data)
 except:raise AssertionError('Non-JSON response '+status+': '+data[:1500])
assert http('a','tickets.php')[0]==401
for who in ['a','b']:
 email='wallet-'+who+'@example.test'
 status,data=http(who,'register.php',dict(email=email,first_name='Wallet',last_name='Fixture '+who,password='WalletProof2026!'));assert status==202,(status,data)
 status,data=http(who,'login.php',dict(email=email,password='WalletProof2026!'));assert status==401,(status,data)
 assert http(who,'tickets.php')[0]==401
 token=php('token',email);assert token
 status,data=http(who,'verify-email.php?token='+urllib.parse.quote(token));assert status==200,(status,data)
 status,data=http(who,'tickets.php');assert status==200,(status,data)
 assert len(data['tickets'])==(2 if who=='a' else 0),data
 if who=='a':tickets=data['tickets'];assert all(t['status']=='active' and t['credential'] for t in tickets)
# Caller-supplied email/id cannot redirect ownership.
status,data=http('b','tickets.php?email=wallet-a%40example.test&attendee_id=1');assert status==200 and not data['tickets']
status,session=http('a','session.php');csrf=session['csrf_token']
status,_=http('a','staff/action.php',dict(action='ticket_check_in',ticket_id=tickets[0]['public_id'],credential=tickets[0]['credential']),csrf);assert status==403
php('staff');status,session=http('b','session.php');csrf=session['csrf_token']
status,_=http('b','staff/action.php',dict(action='ticket_check_in',ticket_id=tickets[0]['public_id'],credential='invalid'),csrf);assert status==400
status,data=http('b','staff/action.php',dict(action='ticket_check_in',ticket_id=tickets[0]['public_id'],credential=tickets[0]['credential']),csrf);assert status==200,(status,data)
status,_=http('b','staff/action.php',dict(action='ticket_check_in',ticket_id=tickets[0]['public_id'],credential=tickets[0]['credential']),csrf);assert status==409
status,data=http('a','tickets.php');assert [t['status'] for t in data['tickets']]==['checked_in','active'],data
# Verified account sign-in also retrieves a pre-existing purchase without reissuance.
status,session=http('a','session.php');http('a','logout.php',{},session['csrf_token'])
status,data=http('a','login.php',dict(email='wallet-a@example.test',password='WalletProof2026!'));assert status==200
status,data=http('a','tickets.php');assert len(data['tickets'])==2
print('PASS HTTP wallet: real registration/verification/login, unverified/anonymous denial, two admissions after purchase-before-account, cross-account/query exclusion, staff capability/CSRF/credential check-in and duplicate rejection; no financial calls or outbound mail.')
