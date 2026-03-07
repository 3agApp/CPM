## Plan: Filament Multi-Tenant Organization System

Build a production-ready multi-tenant Filament 5 dashboard for **CPM** where users belong to multiple Organizations via a membership pivot with roles (Owner/Admin/Member). Includes email-based member invitations with accept links, a tenant-scoped Supplier resource, and org registration/profile editing via Filament's built-in tenancy system.

---

### Phase 1: Database Foundation (Steps 1–7)

1. **Create Organization model + migration + factory + seeder** — `id`, `name`, `slug` (unique). Model implements `HasCurrentTenantLabel`, has `members()` BelongsToMany and `suppliers()` HasMany
2. **Create `organization_user` pivot migration** — `organization_id`, `user_id`, `role` (string, default 'member'), `timestamps`. Unique on `[organization_id, user_id]`
3. **Create `Role` enum** — `app/Enums/Role.php` with `Owner`, `Admin`, `Member` cases and label method
4. **Create Invitation model + migration + factory** — `organization_id`, `email`, `role`, `token` (unique), `expires_at`, `accepted_at`, `invited_by`. Unique on `[org_id, email]` where not accepted. Model has `isExpired()`/`isAccepted()` helpers
5. **Create Supplier model + migration + factory** — `organization_id` (FK), `name`. BelongsTo Organization
6. **Update User model** — Implement `HasTenants` contract, add `organizations()` BelongsToMany with pivot role, implement `getTenants()` and `canAccessTenant()`
7. **Update DatabaseSeeder** — Seed sample orgs and attach test user as Owner

*All steps in Phase 1 are sequential (migrations depend on models, seeder depends on all).*

---

### Phase 2: Filament Panel Configuration (Steps 8–10)

8. **Update DashboardPanelProvider** — Add `->tenant(Organization::class, slugAttribute: 'slug', ownershipRelationship: 'organization')`, `->tenantRegistration(...)`, `->tenantProfile(...)`, update branding to "CPM"
9. **Create `RegisterOrganization` page** — Extends `RegisterTenant`. Form: name + auto-slug. Override `handleRegistration()` to attach current user as Owner
10. **Create `EditOrganizationProfile` page** — Extends `EditTenantProfile`. Form: name + slug. Only Owner/Admin can access

*Depends on Phase 1 completion.*

---

### Phase 3: Authorization (Steps 11–13, *parallel with Phase 2*)

11. **OrganizationPolicy** — `update`: owner/admin. `delete`: owner only
12. **SupplierPolicy** — `viewAny`/`view`: any member. `create`/`update`/`delete`: owner/admin
13. **InvitationPolicy** — `viewAny`/`create`/`delete`: owner/admin

---

### Phase 4: Member Management & Invitations (Steps 14–18)

14. **OrganizationMemberResource** — Filament resource managing `organization_user` pivot. Table: user name, email, role badge, joined date. Actions: change role, remove member (can't demote last owner)
15. **InvitationResource** — Table: email, role, status, invited_by, expiry. Create form: email + role select. On create: generate token, set 48h expiry, dispatch email. Actions: resend, revoke
16. **InvitationMail** (Mailable) — Contains org name, inviter name, role, accept URL with token
17. **Invitation accept route + controller** — `GET /invitations/accept/{token}`. If user exists → attach to org, mark accepted. If not → redirect to registration with token in session
18. **Extend registration flow** — After registration, check session for pending invitation token, auto-attach to org

*Steps 14–15 parallel. Steps 16–18 sequential.*

---

### Phase 5: Tenant-Scoped Supplier Resource (Step 19)

19. **SupplierResource** — Filament resource with table (name) and form (name TextInput). Auto-scoped to current org via Filament's built-in `BelongsToTenant` trait

*Parallel with Phase 4.*

---

### Phase 6: Quality & Verification (Steps 20–23)

20. **Run Pint** — `vendor/bin/pint --dirty --format agent`
21. **Write Pest tests** — Org CRUD, tenant scoping, invitation flow, member roles, supplier tenant isolation, policy enforcement
22. **Run migrations + seed** — `php artisan migrate:fresh --seed`
23. **Run test suite** — `php artisan test --compact`

---

### Relevant Files

**Create:**
- `app/Models/Organization.php` — Tenant model
- `app/Enums/Role.php` — Owner/Admin/Member enum
- `app/Models/Invitation.php` — Invitation with token/expiry
- `app/Models/Supplier.php` — Tenant-scoped supplier
- Migrations for organizations, organization_user, invitations, suppliers
- `app/Filament/Pages/Tenancy/RegisterOrganization.php` — Tenant registration
- `app/Filament/Pages/Tenancy/EditOrganizationProfile.php` — Tenant profile
- `app/Filament/Resources/OrganizationMemberResource.php` — Member management
- `app/Filament/Resources/InvitationResource.php` — Invitation CRUD
- `app/Filament/Resources/SupplierResource.php` — Supplier CRUD
- Policies: Organization, Supplier, Invitation
- `app/Mail/InvitationMail.php` — Invitation mailable
- `app/Http/Controllers/InvitationAcceptController.php` — Accept flow
- Pest feature tests

**Modify:**
- `app/Models/User.php` — Implement `HasTenants`, add `organizations()` relationship
- `app/Providers/Filament/DashboardPanelProvider.php` — Enable tenancy
- `database/seeders/DatabaseSeeder.php` — Seed orgs + memberships
- `routes/web.php` — Invitation accept route

---

### Verification

1. `php artisan migrate:fresh --seed` — all tables created, seeds run
2. `vendor/bin/pint --dirty --format agent` — code style passes
3. `php artisan test --compact` — all tests green
4. **Manual**: Register → create org → see org picker → create supplier → switch org → supplier not visible → invite member → accept link works → role restrictions enforced

### Decisions

- **Roles as enum** (not DB table) — 3-tier stored as string in pivot
- **No `HasDefaultTenant`** — always show org picker
- **Slug-based URLs** — `/dashboard/{org-slug}/...`
- **48-hour invitation expiry** with unique token
- **Invitation accept route outside Filament** — unauthenticated users need access
- **Supplier minimal** — only `name` for now
