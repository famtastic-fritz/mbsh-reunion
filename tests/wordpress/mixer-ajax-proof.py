#!/usr/bin/env python3
import base64, hashlib, hmac, json, time, urllib.parse, subprocess
def http(route,body=None):
 args=['docker','exec','-i','mbsh-mixer-wc-app-20261016','curl','-sS','-b','/tmp/mixer-fixture.cookies','-c','/tmp/mixer-fixture.cookies','-w','\n%{http_code}']
 if body is not None:args+=['--data-binary','@-']
 result=subprocess.run(args+['http://127.0.0.1/'+route],input=body,text=True,capture_output=True,check=True).stdout
 payload,status=result.rsplit('\n',1)
 return int(status),json.loads(payload)
def sign(payload):
 return base64.urlsafe_b64encode(payload.encode()).decode().rstrip('=')+'.'+hmac.new(b't'*64,payload.encode(),hashlib.sha256).hexdigest()
def post(token,quantity=2):
 body=urllib.parse.urlencode(dict(mbsh_order_code='MBSH-ABC123',contact_name='Isolated Mixer Fixture',email='fixture@example.test',quantity=quantity,checkout_token=token))
 return http('?wc-ajax=famtastic_ticket_add_to_cart',body)

old=sign(f'MBSH-ABC123|fixture@example.test|2|{int(time.time())+60}')
status,result=post(old);assert status==400 and result['error'],result
current=sign(f'MBSH-ABC123|fixture@example.test|2|{int(time.time())+60}|mbsh-mixer-2026-11-07-v1')
status,result=post(current);assert status==200 and result['ok'],result
status,replay=post(current);assert status==200 and replay['replayed'],replay
status,cart=http('?rest_route=/wc/store/v1/cart');assert status==200,cart
assert len(cart['items'])==1 and cart['items'][0]['quantity']==2,cart
assert cart['totals']['total_price']=='6000',cart['totals']
status,bad=post(current,3);assert status==400 and bad['error'],bad
print('PASS real WooCommerce AJAX: legacy-token rejection, signed mixer add, duplicate replay, one cart line, $60 final total, tampered quantity rejection')
