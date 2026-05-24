# Supreme-3 — System Documentation

A multi-tenant security workforce management system (Laravel 12 / PHP 8.2). It manages clients, sites, security guards, shifts, patrols, check-calls, invoicing, payroll, training, incidents, and a companion mobile app (Expo / React Native client consuming the API).

---

## 1. Tech stack

- **Backend:** Laravel 12, PHP 8.2
- **Auth (web):** Laravel Breeze (session)
- **Auth (mobile/API):** Laravel Sanctum (Bearer tokens)
- **Roles/Permissions:** Spatie laravel-permission
- **Database:** MySQL (migrations target MySQL-flavoured SQL; an SQLite fallback exists for fresh installs)
- **Frontend (admin web):** Blade + Bootstrap 5 + jQuery + Alpine.js + Tailwind v3/v4 + Vite
- **DataTables:** yajra/laravel-datatables + laravel-datatables-vite
- **PDF:** barryvdh/laravel-dompdf + setasign/fpdi
- **Excel:** maatwebsite/excel
- **Image optimisation:** spatie/image-optimizer
- **Push notifications:** Expo (alymosul/exponent-server-sdk-php)
- **Maps / Geocoding:** Google Maps (Geocoding + Places APIs)
- **External integration:** UK SIA Public Register scraper (`App\Services\SiaLicenceChecker`)

### Composer scripts

- `composer dev` — runs `php artisan serve`, queue worker, log pail, and `npm run dev` concurrently.
- `composer test` — clears config and runs PHPUnit.
- `npm run dev` / `npm run build` — Vite dev server / production build.

---

## 2. Domain overview

The system represents a security company that:

1. Has **Clients** (companies that hire guards) with **Sites** (physical locations to be guarded).
2. Employs **Employees** (security guards), or hires guards via **Subcontractors**.
3. Builds **Shifts** (a recurring schedule tied to a site) which spawn **ShiftDates** (a single calendar instance).
4. Each ShiftDate can have **CheckCalls** (regular welfare check-ins), **Patrols** (a sequence of checkpoint scans), **BookingAlarms**, **Locations** (GPS pings), **Notes** and media.
5. Generates **Invoices** (to clients) and **Payrolls** / staff invoices (to guards and subcontractors).
6. Handles **LeaveRequests / EmployeeLeaves**, **Training Materials & Acknowledgements**, **DOB** (daily occurrence book), **Incident Reports**, **EmergencyAlerts** (panic button), and **DocumentationUploads** with expiry tracking.

---

## 3. Multi-tenancy: the `BelongsToAdmin` trait

Almost every business model (`Client`, `Site`, `Shift`, `ShiftDate`, `Employee`, `Invoice`, `Notification`, `User`, etc.) uses [app/Traits/BelongsToAdmin.php](app/Traits/BelongsToAdmin.php). It implements automatic per-tenant scoping:

| Logged-in user | Sees |
|---|---|
| `admin` role | rows where `admin_id = own user id` |
| `superadmin` | rows where `admin_id IS NULL` (system/global) |
| User with `admin_id` set (controller, staff_leader, control_room, client) | rows where `admin_id = own admin_id` |
| `security_staff` | NOT scoped — uses per-user filters (`staff_id`, `user_id`) |
| Unauthenticated / API routes | Scope is bypassed (API routes have their own auth/filter logic) |

When an admin creates any record, `admin_id` is auto-filled. The trait is bypassed via `Model::withoutAdminScope()` or `Model::withoutGlobalScope('admin_scope')`.

Special case: a curated `config/admin_visible_names.php` allows admin id `8766` to see specific system-level users/employees in addition to their own.

---

## 4. Roles & permissions

Roles seeded in [database/seeders/RoleSeeder.php](database/seeders/RoleSeeder.php):

- `superadmin` — system-wide; gets all permissions
- `admin` — tenant owner; data scoped to themselves
- `client` — client portal user (sees only their own sites, invoices, rota)
- `security_staff` — guard, mobile app user
- `subcontractor` — external company that supplies guards
- Operational roles referenced in code: `controller`, `staff_leader`, `control_room`

Permissions are seeded by `permissionSeeder` and `RolePermissionSeeder`. The HR "docs" routes use Spatie `can:` middleware (`can:Read HR Managment`, `can:Write HR Managment`, etc.).

Notable per-permission gate: `assign-shift-override` — required to call `/assign-shift-override`, `/updateshift/{id}/override`, and `/shifts/multi-assign-override` (these endpoints bypass shift restrictions).

---

## 5. Directory map

```
app/
├── Console/Commands/   one-shot CLI tasks and the shift notifications worker
├── Events/             MessageSent (chat broadcast)
├── Helpers/            Notify.php — global helpers: notify_users, send_push_notification, applyRestrictions
├── Http/
│   ├── Controllers/    web controllers (top-level) + Auth/ + API/ + Docs/
│   └── Middleware/     CloseDbConnection
├── Jobs/               RunSiaCheck
├── Models/             ~70 Eloquent models
├── Services/           PayrollCalculator, InvoiceService, RateResolver, GeoService, SiaLicenceChecker, ShiftAssignmentService, FileCompressor
└── Traits/             BelongsToAdmin, LogsChanges

routes/
├── web.php             admin web app
├── api.php             mobile API
├── auth.php            Breeze auth routes
├── docs.php            HR digital-forms + dynamic-input builder
└── console.php         Schedule::command('shifts:process-notifications')->everyMinute()

database/
├── migrations/         ~90 migrations, see § 11
└── seeders/

config/
├── admin_visible_names.php   curated visibility list (admin 8766)
├── services.php              keys: google_maps, expo, slack, site_geofence
├── permission.php            Spatie
├── sanctum.php
└── gantt.php                 scheduling UI defaults

resources/views/        thin — only auth/reset_code/dashboard/map blades; most UI lives elsewhere
```

---

## 6. Authentication

### Web (sessions)
Laravel Breeze drives login, password reset, email verification — wired in [routes/auth.php](routes/auth.php). Registration is disabled (the GET form is commented out; POST handler remains).

Custom logout in [routes/web.php:155](routes/web.php#L155) handles **impersonation**: if an admin started impersonating a client via `/impersonate/{clientId}` ([routes/web.php:288](routes/web.php#L288)), logout restores the original admin instead of fully logging out.

Login activity is recorded in `login_activities` (login_at / logout_at) via the logout closure.

### Mobile API (Sanctum)
All API routes except auth endpoints sit behind `auth:sanctum`. Tokens are issued via `POST /api/auth/login`.

`AuthAPIController::login` ([app/Http/Controllers/API/AuthAPIController.php](app/Http/Controllers/API/AuthAPIController.php)) does extra work for `security_staff`:

- Captures `device_info` (device_id, name, OS, app_version) and last known location.
- First login: stores a `DeviceLog`.
- Subsequent login from a **different** `device_id`: writes a `DeviceChangeRequest` with status `pending` and notifies admin user_id=1; login is held until an admin approves via `/device-change-requests/action`.

Other auth endpoints: `/auth/logout`, `/auth/forgot-password`, `/auth/verify-reset-code`, `/auth/reset-password`, `/auth/face-verify` (Sanctum-protected face check), `/auth/refresh-token`.

---

## 7. Models — quick reference

Important relationship-bearing models (see [app/Models](app/Models)):

- **User** ([app/Models/User.php](app/Models/User.php)) — central identity. `hasOne` Employee, `hasMany` Site (as client), Invoice, Shift, DeviceLog, Notification, etc. Has `setPasswordAttribute` that ALSO writes `plaintext_password` (used by some legacy flows; consider security implications).
- **Employee** ([app/Models/Employee.php](app/Models/Employee.php)) — guard profile, SIA licence, visa, files, holiday entitlement, bank details. `belongsTo` User. Soft-deletes. Auto-generates a 6-digit `reference_number` on creation.
- **Client** — `belongsTo` User; `hasMany` Site; `belongsTo` manager (Employee).
- **Subcontractor** (`sub_contractors` table) — `belongsTo` User; carries commission %, pay_rate, VAT.
- **Site** — `belongsTo` client (User); `hasMany` Shift, PatrolCheckPoint, SiteStaffRate, SiteHolidayRate, TrainingMaterial. Has `radius` (geofence), `plus_code`, `nfc_tag`, `has_qr`.
- **Shift** — the template (rate, days, times, client/site/staff binding). `hasMany` ShiftDate.
- **ShiftDate** — the per-day instance with 9 statuses: `PENDING (0)`, `DISPATCHED (1)`, `ACCEPTED (2)`, `STARTED (3)`, `ENDED (4)`, `REJECTED (5)`, `CANCELLED (6)`, `PRE_START (7)`, `AWAIT_FINISH (8)`. Stores rate snapshots (`guard_rate`, `site_rate`) at creation time. `hasMany` CheckCall, Patrol, ShiftBooking, Location, ShiftNote.
- **Patrol** — `hasMany` PatrolMedia, CheckpointScan. Belongs to ShiftDate via `shift_id`.
- **CheckCall** — scheduled welfare call; `belongsTo` ShiftDate (`shift_id`) and Employee.
- **PatrolCheckPoint** — physical scan target on a site (QR/NFC).
- **CheckpointScan** — a scan event during a patrol.
- **Invoice** — auto-generates `invoice_number` with prefix per type: `CLI-INV`, `SUB-PAY`, `STAFF-PAY`. Three scopes: `clientInvoices`, `subcontractorInvoices`, `securityStaffInvoices`.
- **InvoiceItem** — one row per ShiftDate (hours, breaks, book-on/off, rate, amount).
- **InvoiceReview** — revision workflow for client-facing invoices.
- **Payroll** — minimal model; payroll calculation lives in services and writes Invoices of type `security_staff`.
- **Notification** — `user_id=1` is the **admin dashboard sentinel** (system notifications). On create, if `admin_id` is null and `action_url` contains a `/shift-dates/{id}/` path, it auto-derives `admin_id` from that ShiftDate. Casts `data` to array, `read` to bool.
- **EmergencyAlert** — panic button; one alert can be cancelled or acknowledged.
- **DeviceToken** — Expo push token (must start with `ExponentPushToken[`).
- **Conversation / Message / MessageRead / ConversationUser / UserPinnedConversation** — in-app chat.
- **TrainingMaterial / TrainingAcknowledgement / ShiftTraining** — required-reading content, linked to shifts.
- **DobEntry / DobMedia** — daily occurrence book.
- **IncidentReport / IncidentMedia / IncidentPerson** — incident workflow with status updates.
- **Vehicle / VehicleCompliance / VehicleMaintenance / RoadworthinessCheck** — fleet management.
- **Restriction / RestrictionOverride** — pluggable rules engine (see § 12).
- **SiaCheckReport** — output of the SIA bulk licence checker.
- **LoginActivity / DeviceLog / DeviceChangeRequest** — login auditing.
- **Log** (polymorphic `loggable`) — change history fed by the `LogsChanges` trait.

---

## 8. Services layer

### `App\Services\PayrollCalculator` ([app/Services/PayrollCalculator.blade.php](app/Services/PayrollCalculator.blade.php))
Computes payroll for an Employee over a period: total hours, breaks, late book-on / early book-off deductions, gross, deductions, net. Also includes SSP (UK statutory £23.75/day, 3 waiting days, cap 28 weeks) and holiday entitlement (12.07% accrual or pro-rated 28 days/year). Filename `.blade.php` appears to be a legacy artefact — the class is plain PHP.

### `App\Services\InvoiceService` ([app/Services/InvoiceService.php](app/Services/InvoiceService.php))
Three top-level generators:

- `generateClientInvoice($clientId, $siteId, $dateFrom, $dateTo, $dueDate, $notes, $frequency)` — client invoice for one site.
- `generateClientInvoiceForSites($clientId, [siteIds], …)` — single invoice across multiple sites.
- `generateSubcontractorInvoice($subcontractorId, $dateFrom, $dateTo, $dueDate, $notes, $securityStaffId)` — pays a subcontractor; applies commission %.
- `generateSecurityStaffInvoice($staffId, $siteId, $dateFrom, $dateTo, $dueDate, $notes)` — direct guard payment.

**Important:** invoice generators read `site_rate` / `guard_rate` **from the ShiftDate snapshot only**. They do **not** fall back to current site/client/employee rate. This guarantees historical invoices stay stable when rates change later. `processShiftDate()` is the per-row calculator; for client invoices `useScheduledHours=true` skips absentee deductions.

### `App\Services\RateResolver` ([app/Services/RateResolver.php](app/Services/RateResolver.php))
The authoritative resolver for `(guard_rate, site_rate)` on a single ShiftDate. Priority:

1. `SiteHolidayRate` matching the shift's calendar date
2. `SiteStaffRate` for `(site_id, user_id)` — guard rate only
3. `Site.guard_rate` / `Site.office_rate`

`propagateForSite(Site, Carbon $effectiveFrom)` rewrites stored rates on future ShiftDates only. Past shifts are deliberately untouched.

### `App\Services\GeoService` ([app/Services/GeoService.php](app/Services/GeoService.php))
Google geocoding wrapper. Heavily tuned for UK + plus codes:

- Cache key versioned (`geo_coords_v6_...`), 24 h TTL.
- Tries **Places API** (`findplacefromtext`) first with plus-code variants, then `geocode` API with progressive fallbacks (full address + postcode component → `country:GB` → postcode-only → bare address).
- Country restriction (`country:GB`) only applied when input looks UK-ish (`isLikelyUkAddress` + UK postcode regex).
- Distinguishes global vs short plus codes.
- Also provides `distanceInMeters` (haversine) and reverse geocoding `getAddressFromCoordinates`.

### `App\Services\SiaLicenceChecker` ([app/Services/SiaLicenceChecker.php](app/Services/SiaLicenceChecker.php))
Scrapes the public UK SIA register. Handles cookie/seed page, hidden anti-forgery inputs, proxy failover (`SIA_HTTP_PROXY_POOL`), retry on transient TLS/proxy errors. Parses licence status into `active|inactive`. Caches results 30 minutes. Bulk runs spawned by `App\Jobs\RunSiaCheck` and `App\Console\Commands\CheckSiaLicences`; reports surface in `SiaReportController` (`/reports/sia`).

### `App\Services\ShiftAssignmentService`
Thin helper: `assignShift(employeeId, shiftData, overrideRestrictionType=null)` — `updateOrCreate` on ShiftDate, and if override given, records a `RestrictionOverride`.

### `App\Services\FileCompressor`
Wraps spatie/image-optimizer for uploads.

---

## 9. Web routes — feature areas

[routes/web.php](routes/web.php) is organised by resource. Most routes sit behind `auth` middleware.

| Area | Examples |
|---|---|
| Employees | `/employees` CRUD, bans (`EmployeeBanController`), pending deletes approval, logs by email, employment-report PDF (`/reports/employment/{employee}/pdf`), SIA processing trigger (`/process-sia-licences`) |
| Subcontractors | `/subcontractors` CRUD, `/subcontractor/{id}/employees`, import/export |
| Clients | `/clients` CRUD, `/clients/{id}/assign-manager`, import/export |
| Sites | `/sites` CRUD, `/sites/{id}/generate-qr`, `/sites/{id}/generate-nfc`, `/sites/geocode`, `/holidays` list |
| Shifts | `/shifts`, `/scheduling`, `/worker_calendar`, `/site_calendar`, `/today_rota`, `/shifts/multi-assign`, `/shifts/multi-edit`, `/shifts/filter`, override variants |
| Shift dates | `/shift-dates/{id}/view`, tabs `/logs` `/checkcalls` `/patrols`; notes CRUD + `/shift-dates/notes/updates` for polling |
| Check calls | web update/approve/reject, status & comment endpoints |
| Patrols | approve/reject, PDF/Excel export, update/delete; `/patrol/{patrol}/locations` (data for map) |
| Invoices | `/invoices`, `/generateinvoice/{id}` (client), `/generateinvoice-sub/{id}` (subcontractor), bulk-delete, exports |
| Payrolls | `/payrolls`, `/generatepayroll`, `/generatepayroll_subcontractor/{id}`, JSON data endpoint for DataTable |
| Roles & Permissions | full CRUD `/roles`, `/permissions`, import/export |
| Users | `/users` CRUD, dashboard `/dashboard` |
| Vehicles | `/vehicle_details`, `/vehicle_management`, compliance + maintenance + roadworthiness CRUD |
| Documentation uploads | `/documentation_uploads` CRUD, `documents/report` for expiry tracking |
| Training | `/hr` (materials) CRUD, `/show/acknowledged/{id}` |
| Leaves (holidays) | `/leaves` CRUD + `/leaves/pending`, approve/reject |
| DOBs | `/dobs` resource + bulk-delete + exports |
| Incidents | `/incidents` resource + status updates + bulk-delete + exports |
| Logs | `/logs` (audit trail) |
| Reports | `/reports/availability`, `/reports/performance`, `/staff-report`, `/booking/report`, `/reports/logins`, `/reports/shifts`, `/reports/clients`, `/reports/checkpoints`, `/reports/salary`, `/reports/sia` |
| Chat | `/chat`, `/conversations` create/list/messages/pin/typing/mark-read |
| Notifications | `/notifications`, `/notifications/json` (polled), mark-all-read, mark-selected-read |
| Tracking | `/track/site/{siteId}` (public live tracking page), `/track/site/{siteId}/data` (JSON poll), `/shift/{shiftId}/map`, `/generate-heatmap` |
| Impersonation | `/impersonate/{clientId}` start, `/impersonate/leave` stop |
| Client portal | `/client/dashboard`, `/client/rota`, `/client/invoices`, `/client/sites/*`, `/client/profile` (role:client) |
| Restrictions UI | `/settings/restrictions`, toggle |
| HR digital forms | see [routes/docs.php](routes/docs.php) — form builder, dynamic fields, submissions, public application/incident forms |
| One-shot ops | `/optimize-server` (clear caches), `/restore-deleted-employees` (legacy restore script — delete after use) |

---

## 10. Mobile API — complete reference

All API responses are JSON. All endpoints below the auth block require `Authorization: Bearer <sanctum_token>`.

### Auth — `/api/auth/*`

| Method | Path | Purpose |
|---|---|---|
| POST | `/login` | Email + password + `device_info` → token. Triggers DeviceChangeRequest if device_id differs. |
| POST | `/logout` | Revokes current token |
| POST | `/forgot-password` | Email a reset code |
| POST | `/verify-reset-code` | Verify code |
| POST | `/reset-password` | Set new password |
| POST | `/face-verify` (auth) | Face check against stored Profile data |
| POST | `/refresh-token` | Issue a fresh token |

### Profile

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/profile` | Current user profile |
| PUT | `/api/profile` | Update profile |
| POST | `/api/profile/face-data` | Upload face embedding/photo |

### Documents

| Method | Path | Purpose |
|---|---|---|
| POST | `/api/documents` | Upload a document |
| GET | `/api/documents` | List own documents |
| GET | `/api/alerts` | Expiring-document alerts |
| GET | `/api/alerts/count` | Count of unread alerts |

### Shifts (mobile)

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/shifts/all` | All assigned shifts |
| GET | `/api/shifts/count` | Count |
| GET | `/api/shifts/calendar` | Calendar feed |
| GET | `/api/shifts/{id}` | Detail |
| POST | `/api/shifts/{shift_id}/respond` | Accept/Reject a dispatched shift |
| POST | `/api/leave-requests` | Submit holiday/sick/unpaid |
| GET | `/api/leave-requests` | List own |
| GET | `/api/leave-requests/{id}` | Single |
| GET | `/api/holiday-Balance` | Remaining holiday hours |
| POST | `/api/shifts/{shift_id}/acknowledge-documents` | Confirm training/documents read for the shift |
| POST | `/api/shifts/{shiftDate_id}/book-on` | Start shift (GPS + photo check possible) |
| POST | `/api/shifts/{shiftDate_id}/book-off` | End shift |
| GET | `/api/alarms/booking` | Booking alarms |
| POST | `/api/alarms/{alarm_id}/acknowledge` | Acknowledge an alarm |
| GET | `/api/shift-status` | Am I currently on duty? |
| GET | `/api/work-hours` | Hours worked summary |
| GET | `/api/shifts/monthly-hours` | Monthly breakdown |

### Check-calls

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/shifts/{shift_id}/check-calls` | List due/done for this shift |
| POST | `/api/check-calls/{id}/complete` | Submit a check-call (media + notes) |
| GET | `/api/alarms/check-calls` | Missed/upcoming alarms |
| POST | `/api/check-calls/phone-complete` | Mark as completed via phone-back |

### Patrols

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/shifts/{shift_id}/patrols` | Routes for a shift |
| POST | `/api/patrols/{patrol_id}/scan` | Scan a checkpoint (QR/NFC, with location + media) |
| POST | `/api/patrols/{patrol_id}/start` | Start patrol |
| POST | `/api/patrols/{patrol_id}/complete` | Finish patrol |
| POST | `/api/patrols/{patrol_id}/media` | Upload media |
| GET | `/api/patrol/{patrol_id}` | Detail |

### DOB (daily occurrence book)

| Method | Path | Purpose |
|---|---|---|
| POST | `/api/dob` | Create entry |
| GET | `/api/dob` | List |
| GET | `/api/dob/{id}` | Detail |
| PUT | `/api/dob/{id}` | Update |

### Incidents

| Method | Path | Purpose |
|---|---|---|
| POST | `/api/incidents` | Create (note: route currently has a trailing space — see [routes/api.php:141](routes/api.php#L141)) |
| GET | `/api/incidents` | List |
| GET | `/api/incidents/{id}` | Detail |
| PUT | `/api/incidents/{id}` | Update |

### Messaging

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/messages/conversations` | List conversations |
| GET | `/api/messages/conversations/{conversation}` | Messages in one |
| POST | `/api/messages` | Send a message |
| POST | `/api/messages/mark-read` | Mark as read |
| POST | `/api/create-conversation` | Start a conversation |
| GET | `/api/users/search` | Search users to message |
| GET | `/api/users/roles` | List roles |

### Location / GPS

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/location/history` | Past pings |
| POST | `/api/location/update` | Push a GPS sample |
| POST | `/api/location/disabled` | Notify location was disabled |
| GET | `/api/activity-check` | Idle detection |

### Emergency / Panic

| Method | Path | Purpose |
|---|---|---|
| POST | `/api/emergency/alert` | Trigger panic |
| POST | `/api/emergency/{alert}/cancel` | Cancel panic |
| POST | `/api/emergency-alerts/{id}/acknowledge` | Admin/responder ack (public — auth not enforced; see [routes/api.php:178](routes/api.php#L178)) |

### Invoices (guard view)

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/invoices/shift-history` | Guard's shift history for invoicing |
| POST | `/api/invoices` | Submit invoice |
| GET | `/api/invoices` | List own / payrolls |
| POST | `/api/invoices/{invoice}/confirm-revision` | Confirm a revision |
| GET | `/api/invoices/{invoiceId}/pdf` | Download PDF |

### Training & Bulletins

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/training` | List materials assigned |
| GET | `/api/training/{id}` | Detail |
| POST | `/api/training/{training_id}/acknowledge` | Mark as read |

### Notifications

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/notifications` | List |
| POST | `/api/notifications/{id}/read` | Mark one read |
| POST | `/api/notifications/register-device` | Register Expo push token |

### Booking media + admin endpoints

| Method | Path | Purpose |
|---|---|---|
| POST | `/api/booking-media` | Upload book-on/off photos |
| GET | `/api/dashboard-alerts` | Admin dashboard alerts |
| POST | `/api/alerts/send-to-guard` | Admin → guard ad-hoc alert |
| DELETE | `/api/admin/messages/{message_id}` | Admin chat moderation |
| PUT | `/api/admin/dob/{entry_id}/edit` | Admin edit DOB |
| POST | `/api/admin/alarms/{alarm_id}/override` | Override a missed alarm |
| GET | `/api/admin/profile-change-requests` | Pending profile-change list |
| GET | `/api/admin/profile-change-requests/{id}` | Detail |
| POST | `/api/admin/profile-change-requests/{id}/approve` | Approve |
| POST | `/api/admin/profile-change-requests/{id}/deny` | Deny |
| GET | `/api/availability` | Get availability |
| PUT | `/api/availability` | Update availability |

### Debug

`Route::any('/api/debug-request')` ([routes/api.php:28](routes/api.php#L28)) echoes method/headers/body for any HTTP method (unauthenticated). Useful for client-side wiring — consider removing in prod.

### Public web routes used by mobile devices

- `GET /track/site/{siteId}` — live site-tracking page (public).
- `GET /track/site/{siteId}/data` — JSON feed (public).
- `GET /shift/{shiftDateId}/locations` — feed used by `shift.locations` map.

---

## 11. Database schema (chronological story)

Migrations [database/migrations/](database/migrations/) tell the build history. Major groups:

**Foundation (May 2025)** — `permission_tables`, `users`, `companies`, `countries`, `cities`, `departments`, `nationalities`, `visa_types`, `employee_types`, `driving_license_types`, `guard_groups`, `security_industry_associations`, `pay_rolls` (initial schema), `sub_contractors`, `sub_contractor_contacts`, `sub_employees`, `clients`, `client_contacts`, `client_site_groups`, `branches`, `client_employee_guards`, `site_trained_gaurds`, `site_trained_sub_contractor_gaurds`, `sites`, `sites_check_calls`, `shift_dates`, `shifts`, `invoices`, `employees`, `employee_type_site`, `holidays`, `vehicles`, `vehicle_compliances`, `vehicle_maintenances`, `roadworthiness_checks`, `documentation_uploads`, `alert_reminders`, `logs`, `licenses`.

**Invoice & terms (June 2025)** — `add_new_columns_to_invoices_table`, `employee_terms`, `shift_checkpoints`, more employee columns, nullable fixes for `clients` / `sites` / `employees`.

**Mobile API foundations (July 2025)** — `employee_leaves`, `patrol_check_points`, `personal_access_tokens`, `device_logs`, `profiles`, `documents`, `dob_entries`, `conversations`, `messages`, `emergency_alerts`, `alarms`, `alerts`, `bank_details`, `booking_alarms`, `checkpoint_scans`, `check_call_media`, `conversation_users`, `dob_media`, `incident_reports`, `invoice_reviews`, `leave_requests`, `message_reads`, `notifications`, `emergency_contacts`, `shift_bookings`, `password_resets`, `reviews`, `add_fields_to_shifts_table`, `training_acknowledgements`, `training_materials`, `add_fields_to_checkpoint_scans_table`, `check_pointscan_media`, `incident_media`, `incident_people`, `add_comment_to_check_calls_table`, `add_staff_id_to_booking_alarms_table`, `device_tokens`, `add_employee_id_to_notifications_table`, `add_notification_flags_to_shifts_table`.

**August 2025** — `add_files_to_employees_table`, `restrictions`, `invoice_items`, `add_invoice_id_to_shift_dates`, `add_status_to_shift_dates_table`, `sick_leaves`, `add_columns_to_employees_table` / `leave_requests_table`, `guard_availabilities`, `shift_trainings`, `shift_notes`, `add_fields_to_shift_dates`.

**September 2025** — `restriction_overrides`.

Run `php artisan migrate` to apply. `database/database.sqlite` auto-created if you scaffold with `composer create-project`.

---

## 12. The Restrictions engine

Located in [app/Helpers/Notify.php](app/Helpers/Notify.php) (`applyRestrictions()`), backed by the `restrictions` table and `RestrictionSeeder`. Used during shift assignment to enforce rules.

Supported `restriction_type`:

- `expiry_check` — error if `entity.field` is past date (e.g. SIA licence expired).
- `required_field_check` — error if field empty.
- `document_check` — error if no approved Document of given type exists.
- `student_visa_hours_check` — 20 h/week cap for student visas (unless date falls inside an active `EmployeeTerm`).
- `min_rest_hours_check` — 12 h rest between shifts.
- `staff_availability_hours` — shift must fit within the user's `Availability` row for that weekday.

UI to toggle restrictions: `/settings/restrictions` ([SettingController](app/Http/Controllers/SettingController.php)).

Overriding requires permission `assign-shift-override` and writes a `RestrictionOverride` for audit.

---

## 13. Notifications & push

Two layers:

1. **DB-stored** (`notifications` table, scoped by `BelongsToAdmin`): rendered by [NotificationsController](app/Http/Controllers/NotificationsController.php) at `/notifications`. Polled via `/notifications/json`. Convention: `user_id=1` denotes a system/dashboard notification visible to all admin-tenant users.
2. **Expo push** (`device_tokens` table): `send_push_notification($userId, $title, $message, $data)` in [app/Helpers/Notify.php](app/Helpers/Notify.php). Skips non-`ExponentPushToken[…]` tokens; auto-deletes invalid ones on Expo errors (`DeviceNotRegistered`, `InvalidCredentials`, etc.).

The mobile app registers a token via `POST /api/notifications/register-device` after login.

---

## 14. Scheduling & background work

[routes/console.php](routes/console.php):

```php
Schedule::command('shifts:process-notifications')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
```

Add `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1` to cron.

Commands ([app/Console/Commands/](app/Console/Commands/)):

- `ProcessShiftNotifications` (`shifts:process-notifications`) — runs every minute; delegates to [ShiftNotificationController::process](app/Http/Controllers/ShiftNotificationController.php).
- `CheckShiftNotifications` — alternative trigger.
- `CheckSiaLicences` (commented schedule for daily 11:47 / 16:00 UK time) — bulk SIA register check.
- `NotifyAdminBeforeDocumentExpiry` — sends expiry warnings.
- `BackfillSitePlusCodes` — populates `sites.plus_code` via GeoService.
- `BackfillEmployeeReference` — fills missing 6-digit refs.
- `Sync(Clients|Employees|SubContractors)ToUsers` — reconcile related User rows.
- `AssignClientUserToSites` / `AssignSubcontractorToEmployee` — data fix-ups.
- `ImportLoginActivitiesFromTokens` — backfill login activity audit.
- `CleanupConnections` — close lingering DB connections.
- `TestSiaRequest` — debug a single SIA lookup.

Jobs ([app/Jobs/](app/Jobs/)):

- `RunSiaCheck` — queued bulk SIA verification.

There is no Laravel Mail config beyond defaults; password reset codes are rendered from `resources/views/reset_code.blade.php`.

---

## 15. Logging & audit trail

`App\Traits\LogsChanges` ([app/Traits/LogsChanges.php](app/Traits/LogsChanges.php)) hooks `creating`/`updating` for any model that uses it (ShiftDate, Site, Client, Employee, Shift, Subcontractor, Patrol, CheckCall, etc.). Writes polymorphic rows into the `logs` table with:

- A resolved actor (request user → Auth user → "System").
- Human-readable diff messages (e.g. "Reassigned from John to Jane", "Created Shift at Acme HQ on 2026-05-24").
- Numeric noise (e.g. `0.00` → `0`) filtered out.
- Special wording for ShiftDate, Patrol, and CheckCall (adds site + date context).

Reviewed at `/logs` (`LogController`) and via the per-entity `*/logs/ajax` endpoints.

---

## 16. Chat module

Web (session-auth) and API both expose conversations. Models: `Conversation`, `Message`, `ConversationUser`, `MessageRead`, `UserPinnedConversation`. Event `App\Events\MessageSent` is the broadcast hook (no broadcaster is wired in routes — verify before depending on real-time).

Web routes: see [routes/web.php:70-92](routes/web.php#L70-L92). API routes: under `/api/messages/*` and `/api/conversations/*`.

---

## 17. Frontend layout

Bundled by Vite ([vite.config.js](vite.config.js)). The admin app uses Blade templates (location not in [resources/views/](resources/views/) — they appear to live in plugin or vendor blade paths inside the project; only three top-level views remain: `dashboard.blade.php`, `map.blade.php`, `reset_code.blade.php`).

Client-side libs: jQuery, Bootstrap 5, Alpine.js, Select2, Summernote, laravel-datatables-vite. CSS uses both Tailwind v3 and v4 (preview) plus Sass.

`npm run dev` starts the Vite server (auto-injected via `@vite` in Blade).

---

## 18. Important conventions & gotchas

- **Plaintext password is stored.** `User::setPasswordAttribute` writes `plaintext_password` alongside the bcrypt hash. Used by recovery flows but is a sensitive-data risk; treat the column as secrets.
- **`user_id = 1` is the dashboard-notification sentinel.** Notifications to admins go there; guards get their own `user_id`. See [routes/web.php:99-137](routes/web.php#L99-L137).
- **Invoice generation reads rate snapshots only.** Editing site/holiday rates after a shift was assigned will NOT retroactively change historical invoices — by design (see `RateResolver` + `InvoiceService`).
- **Mobile API bypasses `BelongsToAdmin` global scope.** API filtering is done per-controller via `staff_id` / `user_id` predicates.
- **Routes file has temporary one-shots.** `/restore-deleted-employees` and `/process-shift-notifications` (web POST) are intentionally callable and should be reviewed/removed as appropriate.
- **`/api/emergency-alerts/{id}/acknowledge` is currently public.** Verify whether this is intended.
- **Trailing space on `POST /api/incidents `** ([routes/api.php:141](routes/api.php#L141)) — clients hitting `/api/incidents` may 404; check before fixing because the API consumer may rely on the literal.
- **PayrollCalculator filename ends `.blade.php`** but contains a regular PHP class. PSR-4 autoload still finds it (because `composer.json` registers `app/`), but renaming to `.php` is safer.
- **DOM PDF + FPDI** are used heavily — keep an eye on memory limits for large exports.

---

## 19. Local development

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# Configure in .env:
#   DB_*                   MySQL connection
#   GOOGLE_MAPS_API_KEY    geocoding
#   EXPO_ACCESS_TOKEN      push notifications
#   SIA_HTTP_PROXY / _POOL optional, for outbound SIA scraping

# 3. Database
php artisan migrate
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=permissionSeeder
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=UserSeeder        # creates initial admin
php artisan db:seed --class=visaTypeSeeder
php artisan db:seed --class=employeeTypeSeeder
php artisan db:seed --class=licenseSeeder
php artisan db:seed --class=RestrictionSeeder

# 4. Run
composer dev   # serves PHP + queue + pail + vite together
# or:
php artisan serve
npm run dev
```

For scheduled tasks locally, run `php artisan schedule:work` in a separate terminal.

---

## 20. Useful entry points

- Dashboard: [/dashboard](routes/web.php#L196) → `UserController::dashboard`
- Auth login form: `/` → `auth.login` view
- API health: `GET /up` (Laravel default health endpoint)
- Live tracking demo: `/track/site/{siteId}`
- Logs viewer: `/logs`
- SIA reports: `/reports/sia`
- Restriction toggles: `/settings/restrictions`

---

