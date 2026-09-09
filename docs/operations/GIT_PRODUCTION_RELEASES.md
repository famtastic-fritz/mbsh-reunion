# Git-addressed production releases and rollback

## Contract

Production application code is selected by an immutable Git commit. The same
release builder and promotion path is used for a forward deployment and a
rollback. A rollback is therefore a deployment of a previously proven commit,
not an ad hoc restoration of copied files.

Git does **not** own or roll back production data. Never replace or delete
WooCommerce orders, Stripe evidence, ticket entitlements, attendee records,
email delivery history, approved uploads, WordPress uploads, databases, or the
secret configuration when rolling application code back.

## Managed application paths

The release builder maps these tracked sources into the unified webroot:

- `frontend/` to `/home/nineoo/public_html/`;
- backend PHP and backend `.htaccess` files to the same webroot;
- `wordpress/wp-content/plugins/famtastic-reunion-platform/` to the matching
  path below `/home/nineoo/public_html/cms/`;
- `wordpress/wp-content/themes/famtastic-event-cinema/` to the matching path
  below `/home/nineoo/public_html/cms/`.

The builder excludes database content, runtime secrets, approved uploads,
WordPress uploads, dependency folders, local artifacts, and non-runtime backend
files. It never uses `rsync --delete` against the shared webroot.

## Release procedure

1. Start from reviewed, committed source and run the relevant test suites.
2. Push the approved commit to the configured production source branch. The
   default is `origin/main`; an explicitly approved release branch may be set
   with `MBSH_PRODUCTION_SOURCE_REF` during a controlled transition.
3. Preview the exact change set:

   ```bash
   ./scripts/deploy-production.sh <commit> --dry-run
   ```

4. Review every `CHANGE` and `RETIRE` line. A retired file is moved into the
   immutable release record rather than deleted.
5. Deploy the same commit:

   ```bash
   ./scripts/deploy-production.sh <commit>
   ```

6. Verify the printed `DEPLOYED` commit, production checksums, PHP syntax,
   public routes, authenticated boundaries, and the task-specific browser
   journey. Record the commit and evidence in the deployment log.

The active production commit is recorded at:

```text
/home/nineoo/.releases/mbsh-reunion/DEPLOYED_COMMIT
```

## Rollback procedure

1. Close consequential entry points when the incident affects checkout,
   ticket issuance, uploads, or outbound mail.
2. Preserve provider events, orders, attempts, logs, and database evidence.
3. Identify the last verified production commit from the deployment record.
4. Preview the rollback:

   ```bash
   ./scripts/rollback-production.sh <previous-commit> --dry-run
   ```

5. Review the change list, then deploy that exact commit:

   ```bash
   ./scripts/rollback-production.sh <previous-commit>
   ```

6. Repeat production syntax, checksum, route, authentication, and browser
   checks. Reconcile provider events only after application behavior is stable.

File archives and database exports remain disaster-recovery evidence. They are
not the normal application-code rollback mechanism.

## Credential rule

Documentation contains only secret locations and rotation procedures. Never
commit passwords, password hashes, API keys, webhook secrets, banking details,
or production configuration values. Authentication recovery uses the product's
approved reset path or an owner-authorized rotation—not a password copied from
documentation.
