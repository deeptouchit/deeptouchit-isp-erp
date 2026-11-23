# Owner Panel: Production Security & MVC Hardening Plan

## Objective

Make the Owner panel production-grade with explicit authorization, secure high-risk actions, protected secrets, reliable audit trails, better performance, and complete regression coverage.

## Phase 1 — Authorization and Policies

- Create an Owner capability matrix for dashboard, billing, tenants, users, backups, gateways, automation, and impersonation.
- Add Laravel Policies/Gates.
- Enforce route-level and controller-level authorization.
- Block self-account privilege escalation and protected super-admin changes.
- Return consistent 403 responses and write authorization audit events.

**Acceptance:** Only authorized Owner capabilities can access or mutate each resource.

## Phase 2 — Impersonation Hardening

- Convert tenant/user impersonation from GET to POST.
- Require CSRF protection and recent owner re-authentication.
- Store initiator, target, timestamp, IP, and user-agent in the impersonation session.
- Provide a clear, audited exit-impersonation flow.

**Acceptance:** Direct URL access cannot start impersonation; every impersonation is auditable.

## Phase 3 — Destructive Action Protection

- Add confirmation modals for tenant/user deletion, failed-job flush, and backup deletion.
- Require re-authentication for destructive or irreversible actions.
- Add dependency checks and soft-delete/recovery where practical.
- Route every destructive operation through the central audit service.

## Phase 4 — Backup Security

- Whitelist backup filenames and enforce an allowed backup directory.
- Reject traversal, encoded traversal, absolute paths, and symlink escapes.
- Protect download, restore-test, upload, and delete endpoints.
- Use signed download URLs where applicable.
- Audit every backup operation.

**Acceptance:** No backup endpoint can read or mutate files outside the approved backup directory.

## Phase 5 — Secret and Credential Protection

- Encrypt gateway, SMS, mail, and provider secrets at rest.
- Never render stored secrets as plain HTML or JSON.
- Use masked inputs and update secrets only when a new value is submitted.
- Require re-authentication and audit logging for reveal/copy actions.
- Exclude secret fields from API responses and logs.

## Phase 6 — Owner User Management

- Define protected roles and protected accounts.
- Block self-delete, self-disable, and unsafe self-password operations.
- Prevent deletion or disabling of the last active super-admin.
- Enforce role allow-lists, email/phone uniqueness, and strong password rules.
- Audit reset, role, status, delete, and impersonation actions.

## Phase 7 — Dashboard Performance

- Consolidate aggregate queries.
- Replace six-month query loops with grouped queries.
- Cache safe KPI values with short, explicit TTLs.
- Move external SMS/provider balance checks to asynchronous or cached reads.
- Track query count and dashboard response time.

**Acceptance:** Dashboard stays within the agreed production response-time and query-count budget.

## Phase 8 — Audit and Observability

- Standardize all high-impact events through one OwnerAuditService.
- Record actor, target, IP, user-agent, timestamp, before/after values, and result.
- Log failed authorization and failed sensitive operations.
- Make audit records append-only/tamper-resistant.
- Surface recent security events for Owner review.

## Phase 9 — Rate Limits and Re-authentication

- Rate-limit Owner login, password reset, impersonation, gateway/SMS tests, backups, and automation dispatch.
- Require recent password confirmation for sensitive actions.
- Add lockout/backoff and clear security audit events.

## Phase 10 — Feature and Security Tests

Add regression coverage for:

- Owner-only route protection and policy enforcement
- GET impersonation blocked; POST impersonation success
- Self-delete/self-disable and protected super-admin protection
- Backup traversal and unauthorized file access blocked
- Secret masking and API redaction
- Destructive-action confirmation, authorization, and audit
- Rate-limit behavior and CSRF protection
- Dashboard KPI correctness and performance
- Impersonation exit and session cleanup

## Delivery Sequence

1. Authorization and policies
2. Impersonation hardening
3. Backup security
4. Secret protection
5. Destructive-action protection
6. User-management hardening
7. Audit standardization
8. Performance optimization
9. Rate limits and re-authentication
10. Full regression/security test suite
11. Staging verification
12. Separate GitHub commit and production deployment

## Engineering Workflow

- Before every edit, open the target file and line in VS Code.
- Make small, reviewable patches.
- Show a focused diff after each edit.
- Run syntax checks and relevant tests before committing.
- Never deploy or alter live state without explicit deployment scope.
