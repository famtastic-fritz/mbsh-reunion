# Production data snapshot proof — 2026-08-16

## Outcome

A read-only export of the existing MBSH production operational tables was imported into a separate local MariaDB database named `mbsh_reunion_prod_snapshot`.

The ordinary portal proof database, `mbsh_reunion`, was not overwritten or merged. No production email, worker, payment, upload, or write endpoint was invoked.

## Imported counts

| Record type | Rows |
| --- | ---: |
| Harry questions | 8 |
| Menu selections | 21 |
| RSVPs | 10 |
| Historical surveys | 88 |
| Time capsules | 7 |
| Poll questions/options/votes | 1 / 3 / 1 |
| Ticket orders | 0 |
| Memories | 0 |
| Memorial entries | 0 |
| Approved/pending sponsors | 0 / 0 |

Security and administration tables (`admin_login_attempts`, `admin_audit_log`, and `rate_limits`) were intentionally excluded.

## Evidence and privacy

The evidence directory is outside the repository at:

`~/.config/famtastic/mbsh-prod-snapshots/20260816T195551Z`

The directory is mode `0700`; its SQL export, count report, and checksum are mode `0600`. The SQL file is intentionally not committed because it contains production personal information and private submissions.

## Repeatable command

```bash
./scripts/import-production-snapshot.sh
```

Each execution creates a timestamped evidence directory and rebuilds only the isolated snapshot database. The export reads production through a transaction-consistent `mysqldump`; it performs no production SQL writes.

## Next controlled step

Build read-only reconciliation adapters from the snapshot into the Committee Desk. Match portal attendees to legacy RSVP/menu/survey records by normalized email, report duplicates and unmatched records, and expose snapshot-derived counts with a visible **Production Snapshot** label. Do not enable edits, sends, or customer-facing visibility until the mapping report and committee acceptance pass.
