# MENUJU — Initial Architecture

**Status:** Foundation proposal. This describes the new application structure; it is not a claim that all modules exist.

## Application shape

- **Laravel** owns authentication, validation, authorization, transactions, persistence, and JSON APIs.
- **Vue 3 + JavaScript** owns route-level screens and reusable interaction components. Vite compiles the application assets; Tailwind carries the shared token system.
- The browser receives an app shell from Laravel. Product data is loaded and changed through authenticated APIs; public invitation routes expose only published invitation data and narrowly scoped RSVP actions.
- MySQL is the source of truth. Use decimal columns for money and serialize amounts as numeric values in the API; never persist formatted Rupiah strings.
- Font Awesome icons and locally bundled design fonts are used so the interface does not depend on remote font requests.

## Proposed source boundaries

```text
app/Http/Controllers/Api/{Auth,Workspace,Preparation,Finance,Invitation}/
app/Http/Requests/{Auth,Workspace,Preparation,Finance,Invitation}/
app/Policies/
app/Models/
app/Services/                 # multi-record domain actions/transactions
database/migrations/
resources/js/
  components/{actions,forms,feedback,navigation}/
  layouts/
  pages/{auth,onboarding,home,preparation,finance,guests,invitations,settings}/
  composables/
  lib/{api,currency,dates}/
routes/{web,api}.php
```

Controllers should remain thin: validate through Form Requests, authorize via Policies or workspace membership checks, call a domain action when a workflow has multiple writes, and return a stable resource response. Do not add a generic repository layer without a concrete need.

## Core domain boundaries

- `User` belongs to one or more `Workspace` records through `workspace_members`; owner and partner are the MVP roles.
- Workspace invitation tokens are stored only as hashes, expire, and are accepted once. Pending invitees have no workspace access.
- Preparation entities (milestones, tasks, events, needs, guests, invitations) are workspace-scoped.
- Financial entities (accounts, transactions, savings goals/deposits, budgets, obligations/payments) are distinct. Transfers, deposits, budget allocations, purchase plans, and actual expenses must not be conflated.
- Public invitation URLs identify a published invitation through an unguessable slug. Public payloads exclude guest contact data and all other workspace data.

## API and security conventions

- Keep application mutations under `/api`; use Laravel session auth and CSRF for first-party browser requests. Public RSVP endpoints are separate, validated, and rate limited.
- Every private query starts from the authorized workspace relationship. Never trust a client-supplied workspace ID or object ID without membership and action checks.
- Use Form Requests for server validation and API Resources for response shapes. Return validation errors field-by-field; do not expose exception traces to clients.
- Multi-record financial actions use database transactions and preserve an audit-friendly correction/void path. Do not hard-delete posted financial mutations casually.
- Generate stable workspace-scoped IDs/slugs without leaking sequential guest or financial record identifiers through public URLs.

## Initial data foundation

The first migration creates users, sessions/password reset support, workspaces, active memberships, and expiring partner invitations. Feature-specific tables should be introduced with the module that defines their verified business rules. All dates for events are interpreted in the workspace/event timezone (`Asia/Jakarta` by default), not silently in the viewer's device timezone.

## Decisions to validate before feature implementation

1. Whether a user may own multiple workspaces or only one active wedding workspace.
2. Whether both MVP roles have equal write rights for all financial actions.
3. The exact linking/reversal semantics between savings deposits and cash-account transactions.
4. Invitation slug and per-guest RSVP token lifecycle, including RSVP change limits.

These decisions must be settled in the relevant module before production migrations or user-visible financial behavior are treated as final.
