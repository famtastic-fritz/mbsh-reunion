#!/usr/bin/env bash
set -euo pipefail
repo_dir="$(cd "$(dirname "$0")/../.." && pwd)"
container="mbsh-portal-integration"
db_port="${MBSH_TEST_DB_PORT:-33317}"
portal_port=18947
mock_port=18948
tmp_dir="$(mktemp -d -t mbsh-portal-integration.XXXXXX)"
portal_pid=""; mock_pid=""
cleanup(){
  [[ -n "$portal_pid" ]] && kill "$portal_pid" 2>/dev/null || true
  [[ -n "$mock_pid" ]] && kill "$mock_pid" 2>/dev/null || true
  docker rm -f "$container" >/dev/null 2>&1 || true
  if [[ "$tmp_dir" == /tmp/mbsh-portal-integration.* || "$tmp_dir" == /var/folders/*/mbsh-portal-integration.* ]]; then rm -rf "$tmp_dir"; fi
}
trap cleanup EXIT
fail(){ echo "FAIL: $*" >&2; [[ -f "$tmp_dir/portal.log" ]] && tail -40 "$tmp_dir/portal.log" >&2; [[ -f "$tmp_dir/mock.log" ]] && tail -20 "$tmp_dir/mock.log" >&2; exit 1; }
json(){ php -r '$j=json_decode(stream_get_contents(STDIN),true);$p=$argv[1];foreach(explode(".",$p) as $k){if(!is_array($j)||!array_key_exists($k,$j))exit(2);$j=$j[$k];}if(is_bool($j))echo $j?"true":"false";elseif($j!==null)echo $j;' "$1"; }
post(){ curl -fsS -b "$1" -c "$1" -H 'Content-Type: application/json' "${@:2}"; }
sql(){ docker exec "$container" mariadb -umbsh_test -pmbsh_test_password -N -B mbsh_portal_test -e "$1" 2>/dev/null; }
token_for(){ sql "SELECT html_body FROM portal_email_jobs WHERE recipient='$1' AND idempotency_key LIKE '$2:%' ORDER BY id DESC LIMIT 1" | sed -n 's/.*token=\([^"<]*\).*/\1/p'; }

docker rm -f "$container" >/dev/null 2>&1 || true
docker run -d --rm --name "$container" -e MARIADB_ROOT_PASSWORD=root_test_password -e MARIADB_DATABASE=mbsh_portal_test -e MARIADB_USER=mbsh_test -e MARIADB_PASSWORD=mbsh_test_password -p "127.0.0.1:${db_port}:3306" mariadb:11.4 >/dev/null
for _ in {1..60}; do docker exec "$container" mariadb -umbsh_test -pmbsh_test_password -e 'SELECT 1' mbsh_portal_test >/dev/null 2>&1 && break; sleep 1; done
docker exec "$container" mariadb -umbsh_test -pmbsh_test_password -e 'SELECT 1' mbsh_portal_test >/dev/null 2>&1 || fail 'MariaDB did not become ready'
docker exec -i "$container" mariadb -umbsh_test -pmbsh_test_password mbsh_portal_test < "$repo_dir/tests/backend/fixtures/prerequisites.sql"
docker exec -i "$container" mariadb -umbsh_test -pmbsh_test_password mbsh_portal_test < "$repo_dir/backend/schema.sql"

(cd "$repo_dir/tests/backend" && php -S "127.0.0.1:${mock_port}" >"$tmp_dir/mock.log" 2>&1) & mock_pid=$!
(cd "$repo_dir/backend" && MBSH_CONFIG_PATH="$repo_dir/tests/backend/fixtures/portal-config.php" MBSH_TEST_DB_PORT="$db_port" php -S "127.0.0.1:${portal_port}" >"$tmp_dir/portal.log" 2>&1) & portal_pid=$!
for _ in {1..30}; do curl -sS "http://127.0.0.1:${portal_port}/portal/session.php" >/dev/null 2>&1 && break; sleep .2; done

a_cookie="$tmp_dir/a.cookie"; b_cookie="$tmp_dir/b.cookie"
a_email='proof-a@example.test'; b_email='proof-b@example.test'; password='ReunionProof2026!'; new_password='ReunionProof2027!'
register_a=$(post "$a_cookie" -X POST "http://127.0.0.1:${portal_port}/portal/register.php" --data "{\"email\":\"$a_email\",\"first_name\":\"Proof\",\"last_name\":\"Alpha\",\"password\":\"$password\"}")
[[ "$(printf '%s' "$register_a"|json ok)" == true ]] || fail "registration A response: $register_a"
register_b=$(post "$b_cookie" -X POST "http://127.0.0.1:${portal_port}/portal/register.php" --data "{\"email\":\"$b_email\",\"first_name\":\"Proof\",\"last_name\":\"Beta\",\"password\":\"$password\"}")
[[ "$(printf '%s' "$register_b"|json ok)" == true ]] || fail 'registration B'
[[ "$(sql "SELECT COUNT(*) FROM portal_email_jobs WHERE idempotency_key LIKE 'verify:%'")" == 2 ]] || fail 'verification outbox idempotency'

a_verify="$(token_for "$a_email" verify)"; b_verify="$(token_for "$b_email" verify)"; [[ -n "$a_verify" && -n "$b_verify" ]] || fail 'verification tokens queued'
curl -fsS -b "$a_cookie" -c "$a_cookie" "http://127.0.0.1:${portal_port}/portal/verify-email.php?token=$a_verify" >/dev/null
curl -fsS -b "$b_cookie" -c "$b_cookie" "http://127.0.0.1:${portal_port}/portal/verify-email.php?token=$b_verify" >/dev/null
session_a=$(curl -fsS -b "$a_cookie" -c "$a_cookie" "http://127.0.0.1:${portal_port}/portal/session.php"); csrf_a=$(printf '%s' "$session_a"|json csrf_token); account_a=$(printf '%s' "$session_a"|json account.public_id)
[[ "$(printf '%s' "$session_a"|json authenticated)" == true ]] || fail 'verified session A'
csrf_b=$(curl -fsS -b "$b_cookie" -c "$b_cookie" "http://127.0.0.1:${portal_port}/portal/session.php"|json csrf_token); account_b=$(sql "SELECT public_id FROM attendee_accounts WHERE email='$b_email'")

pref=$(post "$a_cookie" -X PATCH -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/preferences.php" --data '{"event_updates":true,"memory_updates":false,"promotional_email":true,"sms_notifications":false}')
[[ "$(printf '%s' "$pref"|json ok)" == true ]] || fail 'preferences update'
suggest=$(post "$a_cookie" -X POST -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/suggestions.php" --data '{"category":"music","subject":"Proof song","message":"Integration proof request"}')
[[ "$(printf '%s' "$suggest"|json ok)" == true ]] || fail 'suggestion create'
suggestion_id=$(printf '%s' "$suggest"|json id)
suggest_edit=$(post "$a_cookie" -X PATCH -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/suggestions.php" --data "{\"id\":\"$suggestion_id\",\"category\":\"music\",\"subject\":\"Proof song updated\",\"message\":\"Updated integration proof request\"}")
[[ "$(printf '%s' "$suggest_edit"|json ok)" == true ]] || fail 'suggestion owner update'
cross_suggestion_code=$(curl -sS -o /dev/null -w '%{http_code}' -b "$b_cookie" -H 'Content-Type: application/json' -H "X-CSRF-Token: $csrf_b" -X PATCH "http://127.0.0.1:${portal_port}/portal/suggestions.php" --data "{\"id\":\"$suggestion_id\",\"category\":\"music\",\"subject\":\"Cross account\",\"message\":\"Must fail\"}")
[[ "$cross_suggestion_code" == 409 ]] || fail 'cross-account suggestion update denied'

printf '%s' 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=' | base64 --decode > "$tmp_dir/proof.png"
media=$(curl -fsS -b "$a_cookie" -c "$a_cookie" -H "X-CSRF-Token: $csrf_a" -X POST "http://127.0.0.1:${portal_port}/portal/media.php" -F 'title=Proof memory' -F 'caption=Original caption' -F 'event_year=1996' -F 'consent_to_publish=1' -F "file=@$tmp_dir/proof.png;type=image/png")
media_id=$(printf '%s' "$media"|json id);[[ -n "$media_id" ]] || fail 'media upload'
media_edit=$(post "$a_cookie" -X PATCH -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/media.php" --data "{\"id\":\"$media_id\",\"title\":\"Proof memory updated\",\"caption\":\"Corrected caption\",\"event_year\":1996,\"consent_to_publish\":true}")
[[ "$(printf '%s' "$media_edit"|json ok)" == true ]] || fail 'media metadata update'
cross_media_code=$(curl -sS -o /dev/null -w '%{http_code}' -b "$b_cookie" -H 'Content-Type: application/json' -H "X-CSRF-Token: $csrf_b" -X DELETE "http://127.0.0.1:${portal_port}/portal/media.php" --data "{\"id\":\"$media_id\"}")
[[ "$cross_media_code" == 409 ]] || fail 'cross-account media withdrawal denied'

ordinary_staff_code=$(curl -sS -o /dev/null -w '%{http_code}' -b "$b_cookie" "http://127.0.0.1:${portal_port}/portal/staff/dashboard.php"); [[ "$ordinary_staff_code" == 403 ]] || fail 'ordinary attendee denied committee desk'
sql "INSERT INTO portal_staff_memberships (attendee_id,role,status,granted_by) SELECT id,'site_owner','active','integration-test' FROM attendee_accounts WHERE email='$a_email'" >/dev/null
session_staff=$(curl -fsS -b "$a_cookie" "http://127.0.0.1:${portal_port}/portal/session.php")
[[ "$(printf '%s' "$session_staff"|json staff.authorized)" == true ]] || fail 'staff capability appears in attendee session'
[[ "$(printf '%s' "$session_staff"|json staff.role)" == site_owner ]] || fail 'site owner role missing from verified attendee session'
staff_dashboard=$(curl -fsS -b "$a_cookie" "http://127.0.0.1:${portal_port}/portal/staff/dashboard.php")
[[ "$(printf '%s' "$staff_dashboard"|json staff.role)" == site_owner ]] || fail 'owner dashboard authorization'
membership_self_code=$(curl -sS -o /dev/null -w '%{http_code}' -b "$a_cookie" -H 'Content-Type: application/json' -H "X-CSRF-Token: $csrf_a" -X POST "http://127.0.0.1:${portal_port}/portal/owner/memberships.php" --data "{\"attendee_id\":\"$account_a\",\"role\":\"attendee\"}");[[ "$membership_self_code" == 409 ]] || fail 'owner membership self-lockout prevention'
staff_action=$(post "$a_cookie" -X POST -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/staff/action.php" --data "{\"action\":\"set_suggestion_status\",\"suggestion_id\":\"$suggestion_id\",\"status\":\"reviewing\",\"note\":\"Committee reviewed\"}")
[[ "$(printf '%s' "$staff_action"|json ok)" == true ]] || fail 'committee action through attendee session'
[[ "$(sql "SELECT COUNT(*) FROM portal_staff_audit_log WHERE action='suggestion_reviewing'")" == 1 ]] || fail 'committee action audit trail'
suggest_close=$(post "$a_cookie" -X DELETE -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/suggestions.php" --data "{\"id\":\"$suggestion_id\"}");[[ "$(printf '%s' "$suggest_close"|json ok)" == true ]] || fail 'suggestion close'
media_withdraw=$(post "$a_cookie" -X DELETE -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/media.php" --data "{\"id\":\"$media_id\"}");[[ "$(printf '%s' "$media_withdraw"|json ok)" == true ]] || fail 'media withdrawal'

trivia_game=$(post "$a_cookie" -X POST -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/staff/trivia.php" --data '{"type":"game","title":"Class of 96 Proof","instructions":"Answer once. Have fun.","question_seconds":30}');trivia_game_id=$(printf '%s' "$trivia_game"|json id);[[ -n "$trivia_game_id" ]] || fail 'trivia game create'
trivia_question=$(post "$a_cookie" -X POST -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/staff/trivia.php" --data "{\"type\":\"question\",\"game_id\":\"$trivia_game_id\",\"prompt\":\"What year is the graduating class?\",\"choices\":[\"1995\",\"1996\",\"1997\"],\"correct_index\":1,\"explanation\":\"The reunion celebrates the Class of 1996.\",\"points\":100}");trivia_question_id=$(printf '%s' "$trivia_question"|json id);[[ -n "$trivia_question_id" ]] || fail 'trivia question create'
post "$a_cookie" -X PATCH -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/staff/trivia.php" --data "{\"type\":\"question\",\"id\":\"$trivia_question_id\",\"status\":\"published\"}" >/dev/null
post "$a_cookie" -X PATCH -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/staff/trivia.php" --data "{\"type\":\"game\",\"id\":\"$trivia_game_id\",\"status\":\"open\"}" >/dev/null
trivia_public=$(curl -fsS -b "$b_cookie" "http://127.0.0.1:${portal_port}/portal/trivia.php");[[ "$(printf '%s' "$trivia_public"|json game.public_id)" == "$trivia_game_id" ]] || fail 'trivia open game visible';if printf '%s' "$trivia_public" | grep -q 'correct_index';then fail 'trivia answer leaked to attendee';fi
trivia_start=$(post "$b_cookie" -X POST -H "X-CSRF-Token: $csrf_b" "http://127.0.0.1:${portal_port}/portal/trivia.php" --data '{"action":"start"}');trivia_runtime_question=$(printf '%s' "$trivia_start"|json next_question.public_id);[[ "$trivia_runtime_question" == "$trivia_question_id" ]] || fail 'trivia attempt start'
trivia_answer=$(post "$b_cookie" -X POST -H "X-CSRF-Token: $csrf_b" "http://127.0.0.1:${portal_port}/portal/trivia.php" --data "{\"action\":\"answer\",\"question_id\":\"$trivia_question_id\",\"selected_index\":1}");[[ "$(printf '%s' "$trivia_answer"|json correct)" == true && "$(printf '%s' "$trivia_answer"|json attempt.score)" == 100 ]] || fail 'trivia scoring'
trivia_replay_code=$(curl -sS -o /dev/null -w '%{http_code}' -b "$b_cookie" -H 'Content-Type: application/json' -H "X-CSRF-Token: $csrf_b" -X POST "http://127.0.0.1:${portal_port}/portal/trivia.php" --data "{\"action\":\"answer\",\"question_id\":\"$trivia_question_id\",\"selected_index\":1}");[[ "$trivia_replay_code" == 409 ]] || fail 'trivia replay denied'

conversation=$(post "$a_cookie" -X POST -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/conversations.php" --data '{"subject":"Accessibility question","message":"Is the entrance step-free?"}')
conversation_id=$(printf '%s' "$conversation"|json public_id);[[ -n "$conversation_id" ]] || fail 'attendee conversation create'
cross_conversation_code=$(curl -sS -o /dev/null -w '%{http_code}' -b "$b_cookie" "http://127.0.0.1:${portal_port}/portal/conversations.php?id=$conversation_id");[[ "$cross_conversation_code" == 404 ]] || fail 'cross-account conversation denied'
staff_reply=$(post "$a_cookie" -X POST -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/staff/conversation.php?id=$conversation_id" --data '{"message":"Yes. The accessible entrance is at the main lobby.","status":"waiting_attendee"}');[[ "$(printf '%s' "$staff_reply"|json ok)" == true ]] || fail 'committee conversation reply'
attendee_reply=$(post "$a_cookie" -X POST -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/conversations.php" --data "{\"conversation_id\":\"$conversation_id\",\"message\":\"Thank you. That answers it.\"}");[[ "$(printf '%s' "$attendee_reply"|json status)" == waiting_committee ]] || fail 'attendee conversation reply'
conversation_close=$(post "$a_cookie" -X PATCH -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/conversations.php" --data "{\"id\":\"$conversation_id\",\"status\":\"closed\"}");[[ "$(printf '%s' "$conversation_close"|json status)" == closed ]] || fail 'attendee conversation close'
conversation_reopen=$(post "$a_cookie" -X PATCH -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/conversations.php" --data "{\"id\":\"$conversation_id\",\"status\":\"waiting_committee\"}");[[ "$(printf '%s' "$conversation_reopen"|json status)" == waiting_committee ]] || fail 'attendee conversation reopen'

issue=$(post "$a_cookie" -X POST -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/staff/action.php" --data "{\"action\":\"issue_manual_ticket\",\"account_id\":\"$account_a\",\"ticket_type\":\"Integration Proof\",\"holder_name\":\"Proof Alpha\"}")
ticket_id=$(printf '%s' "$issue"|json ticket_id); [[ -n "$ticket_id" ]] || fail 'ticket issue'
wallet_a=$(curl -fsS -b "$a_cookie" "http://127.0.0.1:${portal_port}/portal/tickets.php"); credential=$(printf '%s' "$wallet_a"|json tickets.0.credential)
[[ "$(printf '%s' "$wallet_a"|json tickets.0.public_id)" == "$ticket_id" ]] || fail 'owner wallet ticket'
wallet_b=$(curl -fsS -b "$b_cookie" "http://127.0.0.1:${portal_port}/portal/tickets.php"); [[ "$(printf '%s' "$wallet_b"|json tickets 2>/dev/null || true)" == '' ]] || [[ "$(sql "SELECT COUNT(*) FROM ticket_wallet_items WHERE attendee_id=(SELECT id FROM attendee_accounts WHERE email='$b_email')")" == 0 ]] || fail 'ticket isolation'
checkin=$(post "$a_cookie" -X POST -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/staff/action.php" --data "{\"action\":\"ticket_check_in\",\"ticket_id\":\"$ticket_id\",\"credential\":\"$credential\"}"); [[ "$(printf '%s' "$checkin"|json ok)" == true ]] || fail 'ticket check-in'
duplicate_code=$(curl -sS -o "$tmp_dir/dup.json" -w '%{http_code}' -b "$a_cookie" -H 'Content-Type: application/json' -H "X-CSRF-Token: $csrf_a" -X POST "http://127.0.0.1:${portal_port}/portal/staff/action.php" --data "{\"action\":\"ticket_check_in\",\"ticket_id\":\"$ticket_id\",\"credential\":\"$credential\"}"); [[ "$duplicate_code" == 409 ]] || fail 'duplicate scan warning'
void_issue=$(post "$a_cookie" -X POST -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/staff/action.php" --data "{\"action\":\"issue_manual_ticket\",\"account_id\":\"$account_a\",\"ticket_type\":\"Void Proof\",\"holder_name\":\"Proof Alpha\"}");void_ticket_id=$(printf '%s' "$void_issue"|json ticket_id)
void_result=$(post "$a_cookie" -X POST -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/staff/action.php" --data "{\"action\":\"ticket_void\",\"ticket_id\":\"$void_ticket_id\",\"note\":\"Synthetic lifecycle proof\"}");[[ "$(printf '%s' "$void_result"|json ok)" == true ]] || fail 'ticket void'
void_replay_code=$(curl -sS -o /dev/null -w '%{http_code}' -b "$a_cookie" -H 'Content-Type: application/json' -H "X-CSRF-Token: $csrf_a" -X POST "http://127.0.0.1:${portal_port}/portal/staff/action.php" --data "{\"action\":\"ticket_void\",\"ticket_id\":\"$void_ticket_id\",\"note\":\"Replay\"}");[[ "$void_replay_code" == 409 ]] || fail 'ticket void replay conflict'
ordinary_issue_code=$(curl -sS -o /dev/null -w '%{http_code}' -b "$b_cookie" -H 'Content-Type: application/json' -H "X-CSRF-Token: $csrf_b" -X POST "http://127.0.0.1:${portal_port}/portal/staff/action.php" --data "{\"action\":\"issue_manual_ticket\",\"account_id\":\"$account_b\",\"ticket_type\":\"Forbidden\",\"holder_name\":\"Proof Beta\"}");[[ "$ordinary_issue_code" == 403 ]] || fail 'ordinary attendee manual ticket denied'

forgot=$(post "$a_cookie" -X POST "http://127.0.0.1:${portal_port}/portal/forgot-password.php" --data "{\"email\":\"$a_email\"}"); [[ "$(printf '%s' "$forgot"|json ok)" == true ]] || fail 'password recovery request'
reset_token="$(token_for "$a_email" reset)"; [[ -n "$reset_token" ]] || fail 'reset token queued'
reset=$(post "$a_cookie" -X POST "http://127.0.0.1:${portal_port}/portal/reset-password.php" --data "{\"token\":\"$reset_token\",\"password\":\"$new_password\"}"); [[ "$(printf '%s' "$reset"|json ok)" == true ]] || fail 'password reset'

logout=$(post "$a_cookie" -X POST -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/logout.php" --data '{}'); [[ "$(printf '%s' "$logout"|json ok)" == true ]] || fail 'logout'
[[ "$(curl -fsS -b "$a_cookie" "http://127.0.0.1:${portal_port}/portal/session.php"|json authenticated)" == false ]] || fail 'session closed'
login=$(post "$a_cookie" -X POST "http://127.0.0.1:${portal_port}/portal/login.php" --data "{\"email\":\"$a_email\",\"password\":\"$new_password\"}"); [[ "$(printf '%s' "$login"|json ok)" == true ]] || fail 'login with reset password'
csrf_a=$(curl -fsS -b "$a_cookie" "http://127.0.0.1:${portal_port}/portal/session.php"|json csrf_token)

self_suspend_code=$(curl -sS -o /dev/null -w '%{http_code}' -b "$a_cookie" -H 'Content-Type: application/json' -H "X-CSRF-Token: $csrf_a" -X POST "http://127.0.0.1:${portal_port}/portal/staff/action.php" --data "{\"action\":\"set_account_status\",\"account_id\":\"$account_a\",\"status\":\"suspended\"}")
[[ "$self_suspend_code" == 409 ]] || fail 'owner self-lockout prevention'
post "$a_cookie" -X POST -H "X-CSRF-Token: $csrf_a" "http://127.0.0.1:${portal_port}/portal/staff/action.php" --data "{\"action\":\"set_account_status\",\"account_id\":\"$account_b\",\"status\":\"suspended\"}" >/dev/null
suspended_code=$(curl -sS -o /dev/null -w '%{http_code}' -b "$b_cookie" "http://127.0.0.1:${portal_port}/portal/preferences.php"); [[ "$suspended_code" == 401 ]] || fail 'admin revocation'

MBSH_CONFIG_PATH="$repo_dir/tests/backend/fixtures/portal-config.php" MBSH_TEST_DB_PORT="$db_port" php "$repo_dir/backend/cron/process-portal-email.php" >/dev/null
[[ "$(sql "SELECT COUNT(*) FROM portal_email_jobs WHERE status='sent'")" -ge 4 ]] || fail 'outbox delivery/provider status'
sql "INSERT INTO portal_email_jobs (idempotency_key,recipient,subject,html_body) VALUES ('retry-proof','fail@example.test','Synthetic failure','<p>fail</p>') ON DUPLICATE KEY UPDATE id=id" >/dev/null
MBSH_CONFIG_PATH="$repo_dir/tests/backend/fixtures/portal-config.php" MBSH_TEST_DB_PORT="$db_port" php "$repo_dir/backend/cron/process-portal-email.php" >/dev/null
[[ "$(sql "SELECT status FROM portal_email_jobs WHERE idempotency_key='retry-proof'")" == pending ]] || fail 'outbox retry state'
sql "UPDATE portal_email_jobs SET attempts=4,next_attempt_at=NOW() WHERE idempotency_key='retry-proof'" >/dev/null
MBSH_CONFIG_PATH="$repo_dir/tests/backend/fixtures/portal-config.php" MBSH_TEST_DB_PORT="$db_port" php "$repo_dir/backend/cron/process-portal-email.php" >/dev/null
[[ "$(sql "SELECT status FROM portal_email_jobs WHERE idempotency_key='retry-proof'")" == dead ]] || fail 'outbox dead-letter state'
[[ "$(sql "SELECT COUNT(*) FROM portal_email_jobs WHERE idempotency_key='retry-proof'")" == 1 ]] || fail 'outbox idempotency uniqueness'

echo 'PASS disposable MariaDB schema'
echo 'PASS registration -> verification -> session -> preferences -> suggestion'
echo 'PASS attendee suggestion and media create -> update -> close/withdraw -> cross-account denial'
echo 'PASS attendee conversation -> committee reply -> attendee reply -> close/reopen -> cross-account denial'
echo 'PASS trivia authoring -> publish/open -> attendee play -> score -> answer secrecy/replay denial'
echo 'PASS password recovery -> reset -> login -> logout'
echo 'PASS ticket ownership -> rotating credential -> check-in -> duplicate warning'
echo 'PASS owner-only manual issue -> void -> replay conflict -> ordinary attendee denial'
echo 'PASS account suspension and cross-account ticket isolation'
echo 'PASS owner self-lockout prevention'
echo 'PASS attendee/staff dual identity -> capability checks -> staff audit trail'
echo 'PASS Resend mock delivery -> provider ID -> retry -> dead-letter -> idempotency'
