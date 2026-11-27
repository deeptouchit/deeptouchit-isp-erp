# Owner Filter & Table Browser Validation Checklist

## Scope

Validate the Owner master listing pages after authentication:

- `/owner/tenants`
- `/owner/users`
- `/owner/plans`
- `/owner/wallets`
- `/owner/billing`
- `/owner/subscriptions`
- `/owner/payments`
- `/owner/transactions`
- `/owner/payment-gateways`
- `/owner/sms-gateways`
- `/owner/mail-settings`
- `/owner/revenue-report`
- `/owner/activity-logs`
- `/owner/sms-logs`
- `/owner/network-engines`
- `/owner/network-engines/audit-logs`
- `/owner/tickets`

## Viewport checks

For each applicable page, verify at:

- [ ] Desktop: 1440px wide
- [ ] Tablet: 768px wide
- [ ] Mobile: 320px wide

Expected result:

- No horizontal page overflow; tables may scroll only inside their table wrapper.
- Filter controls remain usable and keep the shared 34px control height.
- Filter and Reset remain visible in the expected order.
- Empty states do not create forced blank vertical space.
- Action menus remain inside the viewport and are not clipped by table containers.
- Modals fit the viewport and can be closed without zooming or scrolling the page unexpectedly.

## Keyboard and accessibility checks

- [ ] Tab reaches search, filters, More Filters, Filter and Reset in a logical order.
- [ ] Focus ring is clearly visible on every interactive filter control.
- [ ] More Filters exposes the correct expanded/collapsed state.
- [ ] More Filters controls the correct secondary filter drawer.
- [ ] Search clear controls have an accessible name.
- [ ] Three-dot action buttons have an accessible name and close on outside click/Escape where supported.
- [ ] Modal close controls are reachable and visibly focused.
- [ ] Empty state is announced as status content where applicable.

## Functional smoke checks

- [ ] Search submits and preserves the entered query.
- [ ] Secondary filters open when active and preserve all selected values after submit.
- [ ] Reset clears all filter query parameters.
- [ ] Pagination preserves active filters.
- [ ] Table action menus open on the correct row and do not shift the table layout.

## Result log

| Date | Browser | Viewport | Pages checked | Result | Notes |
| --- | --- | --- | --- | --- | --- |
|  | Chrome/Edge | 1440px / 768px / 320px |  | Pending |  |

## Automated validation already completed

- `php artisan view:cache`
- `php artisan test tests/Feature/OwnerPortalTest.php --no-coverage`
- Vite production build
- `git diff --check`
- Sidebar route/view inventory
- Duplicate and non-standard master-table audit
