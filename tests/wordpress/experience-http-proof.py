#!/usr/bin/env python3
"""Actual HTTP/DB/Woo journeys in run-mixer-proof.py's disposable isolated environment."""
import json, pathlib, subprocess, urllib.parse, uuid
APP='mbsh-mixer-wc-app-20261016'
def command(*args, **kw):
    return subprocess.run(args,text=True,capture_output=True,check=True,**kw).stdout.strip()
def fixture(mode):
    return command('docker','exec',APP,'php','-d','memory_limit=512M','/proof/experience-proof.php',mode)
def http(who,path,body=None,method=None,csrf=None,upload=None,raw=False):
    args=['docker','exec',APP,'curl','-sS','-b','/tmp/wallet-'+who+'.cookies','-c','/tmp/wallet-'+who+'.cookies','-w','\n%{http_code}']
    if method: args+=['-X',method]
    if body is not None:args+=['-H','Content-Type: application/json','--data-raw',json.dumps(body)]
    if csrf: args+=['-H','X-CSRF-Token: '+csrf]
    if upload:
        for key,value in upload.items():args+=['-F',key+'='+value]
    result=command(*args,'http://127.0.0.1/portal/'+path)
    data,status=result.rsplit('\n',1)
    if raw:return int(status),data
    try:return int(status),json.loads(data)
    except ValueError:raise AssertionError('Non-JSON '+status+': '+data[:600])
def ok(who,path,body=None,method=None,csrf=None,status=200):
    code,data=http(who,path,body,method,csrf)
    assert code==status,(path,code,data)
    return data
def csrf(who):return ok(who,'session.php')['csrf_token']
def action(who,path,data,status=200,method=None):return ok(who,path,data,method,csrf(who),status)
def register(who,first,last,verify=True):
    email='experience-'+who+'@example.test'
    ok(who,'register.php',dict(email=email,first_name=first,last_name=last,password='ExperienceProof2026!'),status=202)
    if verify:
        token=command('docker','exec',APP,'php','/proof/wallet-proof.php','token',email)
        ok(who,'verify-email.php?token='+urllib.parse.quote(token))

register('c','Casey','Fixture');register('n','Nora','Fixture');register('p','Pending','Fixture',False)
assert http('anonymous','experience.php')[0]==401
assert http('p','experience.php')[0]==401
assert ok('a','experience.php')['entitled'] is True
assert ok('c','experience.php')['entitled'] is True
assert ok('n','experience.php')['entitled'] is False
for endpoint,payload in [('trivia.php',{'action':'start'}),('guess-who.php',{'action':'submit','clues':['First clue here','Second clue here','Third clue here'],'consent_to_publish':True}),('suggestions.php',{'category':'music','subject':'Test song','message':'Test artist'})]:
    assert http('n',endpoint,payload,csrf=csrf('n'))[0]==403,endpoint
assert http('a','guess-who.php',{'action':'withdraw'})[0]==403
assert http('a','staff/experience.php')[0]==403
assert http('a','staff/trivia.php')[0]==403
print('PASS entitlement: anonymous/unverified/unpaid denied; actual paid and checked-in admissions accepted; CSRF and ordinary staff denial.')

# Retry-safe queues, owner-only edits, and visible committee disposition.
key=str(uuid.uuid4());payload=dict(category='music',subject='A source-checked 1996 song',message='Synthetic song request for isolated proof.',request_key=key)
song=action('a','suggestions.php',payload,status=201)['id']
retry=action('a','suggestions.php',payload);assert retry['id']==song
assert http('c','suggestions.php',dict(id=song,category='music',subject='Cross account',message='Forbidden'),method='PATCH',csrf=csrf('c'))[0] in [404,409]
idea=action('a','suggestions.php',dict(category='event',subject='A quick table game',message='Synthetic game idea.',request_key=str(uuid.uuid4())),status=201)['id']
action('b','staff/experience.php',dict(action='set_suggestion_status',id=song,status='accepted',note='Added to the test DJ queue.'))
action('b','staff/experience.php',dict(action='set_suggestion_status',id=idea,status='reviewing',note='Committee reviewing.'))
saved={r['public_id']:r for r in ok('a','suggestions.php')['suggestions']};assert saved[song]['status']=='accepted' and saved[idea]['status']=='reviewing'
print('PASS song/game queues: real persistence, stable retry, cross-account denial, committee statuses returned to attendee.')

# Own clues, consent and withdrawal before round selection.
clues=['I was in the school band.','My favorite class was art.','I kept my graduation program.']
assert http('a','guess-who.php',dict(action='submit',clues=clues[:2],consent_to_publish=True),csrf=csrf('a'))[0]==422
assert http('a','guess-who.php',dict(action='submit',clues=clues,consent_to_publish=False),csrf=csrf('a'))[0]==422
suba=action('a','guess-who.php',dict(action='submit',clues=clues,consent_to_publish=True))['id']
assert action('a','guess-who.php',dict(action='submit',clues=clues,consent_to_publish=True))['id']==suba
subc=action('c','guess-who.php',dict(action='submit',clues=['I ran track after school.','I carried a red backpack.','I loved science projects.'],consent_to_publish=True))['id']
action('c','guess-who.php',dict(action='withdraw'))
assert ok('c','guess-who.php')['submission']['status']=='withdrawn'
action('c','guess-who.php',dict(action='submit',clues=['I ran track after school.','I carried a red backpack.','I loved science projects.'],consent_to_publish=True))
for sid in [suba,subc]:action('b','staff/experience.php',dict(action='review_guess',id=sid,status='approved'))
round_id=action('b','staff/experience.php',dict(action='create_guess_round',title='Isolated Guess Who round',submission_ids=[suba,subc]),status=201)['id']
action('b','staff/experience.php',dict(action='set_guess_round',id=round_id,status='open'))
for who,answer in [('a','Casey Fixture'),('c','Wallet Fixture a')]:
    state=ok(who,'guess-who.php')['round'];encoded=json.dumps(state)
    assert state['score'] is None and 'reveal_name' not in encoded and 'answer_name' not in encoded and 'answer_aliases' not in encoded and answer not in encoded
    card=next(x for x in state['cards'] if not x['is_own'])
    own=next(x for x in state['cards'] if x['is_own'])
    assert http(who,'guess-who.php',dict(action='guess',round_id=round_id,card_id=own['public_id'],answer=answer),csrf=csrf(who))[0]==409
    result=action(who,'guess-who.php',dict(action='guess',round_id=round_id,card_id=card['public_id'],answer=answer))
    assert not any(x in result for x in ['correct','score','points_awarded','answer_name'])
    replay=action(who,'guess-who.php',dict(action='guess',round_id=round_id,card_id=card['public_id'],answer='Changed guess'))
    assert replay['already_recorded'] is True
action('b','staff/experience.php',dict(action='set_guess_round',id=round_id,status='closed'))
assert 'reveal_name' not in json.dumps(ok('a','guess-who.php'))
action('b','staff/experience.php',dict(action='set_guess_round',id=round_id,status='published'))
results=ok('a','guess-who.php')['round'];assert results['score']==200 and len(results['winners'])==2 and all(x['score']==200 and x['rank']==1 for x in results['winners'])
assert all('reveal_name' in x for x in results['cards'])
print('PASS Guess Who: consent, edit/withdraw/resubmit, committee selection, secret answers, one-guess replay protection, own-card denial, delayed reveal, server score and shared tie.')

# A small fixture game exercises the full real scoring lifecycle independently of the research bank.
game=action('b','staff/trivia.php',dict(type='game',title='Isolated two-question proof',instructions='Two synthetic proof questions.'),status=201)['id']
questions=[]
for i in range(2):
    q=action('b','staff/trivia.php',dict(type='question',game_id=game,prompt='Synthetic proof question '+str(i),choices=['Correct fixture','Other fixture'],correct_index=0,explanation='Synthetic proof only.',sort_order=i),status=201)['id'];questions.append(q)
    action('b','staff/trivia.php',dict(type='question',id=q,status='published'),method='PATCH')
action('b','staff/trivia.php',dict(type='game',id=game,status='open'),method='PATCH')
for who in ['a','c']:
    first=action(who,'trivia.php',dict(action='start'));again=action(who,'trivia.php',dict(action='start'));assert first['attempt']['public_id']==again['attempt']['public_id']
    assert 'correct_index' not in json.dumps(first['next_question']) and 'explanation' not in first['next_question']
    assert http(who,'trivia.php',dict(action='answer',question_id=questions[1],selected_index=0),csrf=csrf(who))[0]==409
    for q in questions:
        state=action(who,'trivia.php',dict(action='answer',question_id=q,selected_index=0))
        assert http(who,'trivia.php',dict(action='answer',question_id=q,selected_index=1),csrf=csrf(who))[0]==409
    assert state['attempt']['score']==200 and state['attempt']['status']=='completed'
assert http('b','staff/trivia.php',dict(type='question',id=questions[0],status='retired'),method='PATCH',csrf=csrf('b'))[0]==409
action('b','staff/trivia.php',dict(type='game',id=game,status='closed'),method='PATCH')
assert http('a','trivia.php',dict(action='start'),csrf=csrf('a'))[0]==409
action('b','staff/trivia.php',dict(type='game',id=game,status='published'),method='PATCH')
published=ok('a','experience.php')['results']['trivia'];assert any(len(x['winners'])==2 for x in published)
bank=action('b','staff/trivia.php',dict(type='import_starter'),status=201)
retry=action('b','staff/trivia.php',dict(type='import_starter'));assert retry['id']==bank['id']
staff=ok('b','staff/trivia.php');bank_questions=[q for q in staff['questions'] if q['game_id']==bank['id']]
assert len(bank_questions)==30 and all(q['sources'] for q in bank_questions)
assert next(g for g in staff['games'] if g['public_id']==bank['id'])['status']=='draft'
print('PASS trivia: ordered server scoring, retries, no duplicate answers, frozen questions, closed-round denial, ties/published results, 30-source-question draft import without duplicate.')

# File transfer is real multipart; no public original or unrevised file response.
upload=dict(title='Private test photo',caption='Synthetic image, no person.',event_year='1996',consent_to_publish='0',file='@/tmp/experience-photo.png;type=image/png')
assert http('n','media.php',csrf=csrf('n'),upload=upload)[0]==403
bad=dict(upload,file='@/tmp/experience-disguised.jpg;type=image/jpeg');assert http('a','media.php',csrf=csrf('a'),upload=bad)[0]==400
code,photo=http('a','media.php',csrf=csrf('a'),upload=upload);assert code==201,(code,photo)
pid=photo['id'];code,retry=http('a','media.php',csrf=csrf('a'),upload=upload);assert code==200 and retry['id']==pid
assert ok('a','experience.php')['gallery']==[]
assert http('a','media-file.php?id='+pid,raw=True)[0]==404
assert http('c','media.php',dict(id=pid),method='DELETE',csrf=csrf('c'))[0]==409
assert http('a','media-file.php?id='+pid+'&review=1',raw=True)[0]==403
assert http('b','staff/experience.php',dict(action='review_media',id=pid,status='approved'),csrf=csrf('b'))[0]==422
action('a','media.php',dict(id=pid,title='Private test photo',caption='Permission granted for synthetic proof.',event_year=1996,consent_to_publish=True),method='PATCH')
action('b','staff/experience.php',dict(action='review_media',id=pid,status='approved',note='Synthetic safe photo.'))
gallery=ok('c','experience.php')['gallery'];assert len(gallery)==1 and gallery[0]['public_id']==pid
# Avoid decoding JPEG as UTF-8; curl saves it in the disposable container and emits only status/type.
preview=command('docker','exec',APP,'curl','-sS','-b','/tmp/wallet-c.cookies','-o','/tmp/experience-safe.jpg','-w','%{http_code} %{content_type}','http://127.0.0.1/portal/media-file.php?id='+pid)
assert preview=='200 image/jpeg',preview
assert http('anonymous','media-file.php?id='+pid,raw=True)[0]==401
action('a','media.php',dict(id=pid),method='DELETE');assert ok('c','experience.php')['gallery']==[]
assert http('c','media-file.php?id='+pid,raw=True)[0]==404
print('PASS media: real private upload, MIME rejection, stable retry, pending invisibility, staff capability, approved JPEG derivative, cross-account protection and withdrawal.')

fixture('member');member=ok('n','staff/experience.php');assert member['guess_submissions']==[] and member['guess_rounds']==[]
assert http('n','staff/trivia.php')[0]==403
assert http('n','staff/experience.php',dict(action='create_guess_round',title='Forbidden',submission_ids=[suba,subc]),csrf=csrf('n'))[0]==403
fixture('refund');assert ok('c','experience.php')['entitled'] is False
assert http('c','suggestions.php',payload,csrf=csrf('c'))[0]==403
assert ok('c','session.php')['authenticated'] is True
assert ok('c','guess-who.php')['submission']['public_id']==subc
action('c','guess-who.php',dict(action='withdraw'))
assert ok('c','guess-who.php')['submission']['status']=='withdrawn'
assert ok('a','guess-who.php')['round'] is None
assert not ok('a','experience.php')['results']['guess_who']
audit=json.loads(fixture('audit'));assert audit['audit_rows']>15 and audit['outbound_activity_jobs']==0,audit
print('PASS roles/refund/audit: member restricted, refunded admission loses participation without losing account; consent withdrawal after refund removes published round; mutations audited and no activity emails queued.')
print('PASS full isolated experience HTTP journeys; synthetic identities only, provider network blocked.')
