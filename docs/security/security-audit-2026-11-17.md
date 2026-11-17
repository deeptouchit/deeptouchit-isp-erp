# Security audit and remediation ? 17 November 2026

Application security changes are ready for release through GitHub push and the existing webhook. Device certificate changes are explicitly deferred by the user.

## Final scope

- OLT connection code is restored exactly to the original version. MikroTik TLS connection behavior is also retained. No OLT certificate, firmware, NAT rule or saved device configuration was changed.
- Certificate-verification limitations on these legacy device connections remain an accepted, deferred risk. The prepared replacement certificates were never installed or trusted.
- SSH password removal/rotation remains deferred at the user's request. AGENTS.md was not modified.

## Implemented

- Network routes require their own module permission, plus explicit configure/delete/reboot grants where appropriate. Role permission revocation is no longer cached statically.
- Invalid tenant membership is rejected instead of assigning the first company. Tenant context is scoped to the request, inactive accounts are blocked, and reseller membership is checked.
- Production cannot enable unsigned payment callbacks. Signed adapter callbacks require a fresh timestamp. Transactions bind the gateway, validate amount and supplied currency, preserve idempotency and ignore unknown status values.
- bKash provider confirmations validate reference, completed status, amount and currency. Nagad cannot fabricate success when verification fails or credentials are absent. Excessive webhook header/body logging is removed.
- Owner login is throttled. Branding uploads validate image content/type/size and use generated names. Runtime logs use group-private permissions.
- GitHub deployment requires a secret/signature, matching repository and branch. Both public/API entry points share the same handler. A fixed, root-owned no-argument runner performs code/build operations as the deployment user; root operations are limited to the configured runtime directories and graceful PHP reload. The script serializes deployments and no longer uses wildcard Git trust or 777 permissions.
- Missing PPP passwords are rejected instead of falling back to a predictable shared password.
- Fresh-install migration definitions match inspected live schema. Existing tables are skipped without data backfill or enum rewrites. Partial payments retain the production-supported unpaid state with correct paid/due balances.
- PHP and frontend lockfiles were updated to compatible fixes. Composer and npm advisory reports are clean; the frontend production build passed.

## Live preparation and checks

- Correct live SSH host and administrator access were verified. The other server was not used.
- A random shared GitHub webhook secret is configured on GitHub and server; no secret value is in the audit artifacts. GitHub ping returned 200 OK.
- Nginx upload/storage script protection is installed, syntax-tested and reloaded. Existing temporary PHP-extension probe: 404; text control: 200. Both probes were removed.
- The webhook worker lacked repository write permission. A restricted server runner and validated sudo rule resolve this without granting general sudo access. A backed-up authenticated transition handler will hand off the initial push, then be replaced by the normal tracked handler during deployment.
- Live schema metadata was read before create-only migration reconciliation. All existing production migrations were already applied.

## Validation

- Security suite: 33 tests, 68 assertions passed.
- Tenant authentication and billing/webhook/automation regression: 22 tests, 120 assertions passed.
- PHP syntax, JSON parsing, deployment shell syntax, Composer validation and dependency advisory checks passed. The standalone public webhook is also exercised in a fresh PHP process, including request binding before HTTP kernel bootstrap.
- Provider HTTP traffic in automated tests is mocked. The user separately confirmed working sandbox payments; no real payment was initiated by the agent.
- The full legacy test suite was not certified. No claim is made that every application route has undergone penetration testing.

Deployment completion and the resulting live commit are verified after push and reported in the release response. OLT certificate replacement is not a release prerequisite under the user's latest instruction.
