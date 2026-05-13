<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

/**
 * BelongsToAdmin
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 * @method static void creating(callable $callback)
 * @method static void addGlobalScope($scope, $implementation = null)
 * @method static \Illuminate\Database\Eloquent\Builder withoutGlobalScope($scope)
 * @method \Illuminate\Database\Eloquent\Builder newQueryWithoutScope($scope)
 *
 * Attach this trait to any Eloquent model that should be scoped per admin user.
 *
 * Scoping rules (applied automatically via a global scope):
 *   - admin       → sees only records where admin_id = their own user ID
 *   - non-security users with admin_id set
 *                 → sees only records where admin_id = that admin_id
 *   - superadmin  → sees only records where admin_id IS NULL (system/global records)
 *   - all others  → sees only records where admin_id IS NULL
 *
 * When an admin user creates a record the admin_id is automatically set
 * to their user ID. Other users leave admin_id as null.
 */
trait BelongsToAdmin
{
    public static function bootBelongsToAdmin(): void
    {
        // ── Auto-fill admin_id on create ────────────────────────────────────
        static::creating(function ($model) {
            // Only set admin_id if not already explicitly provided.
            if (!is_null($model->admin_id)) {
                return;
            }

            // Use hasUser() only for the User model to avoid infinite recursion.
            // For every other model Auth::check() is reliable and correct.
            $isUserModel = is_a($model, \App\Models\User::class);
            $isAuth = $isUserModel ? Auth::hasUser() : Auth::check();

            if (!$isAuth) {
                return;
            }

            /** @var \App\Models\User $user */
            $user = Auth::user();

            if ($user->hasRole('admin')) {
                $model->admin_id = $user->id;
                return;
            }

            // Non-security users under an admin should write into their admin-owned dataset.
            if (!$user->hasRole('security_staff') && !is_null($user->admin_id)) {
                $model->admin_id = $user->admin_id;
            }
        });

        // ── Global query scope ───────────────────────────────────────────────
        static::addGlobalScope('admin_scope', function (Builder $builder) {
            // Querying the User model calls Auth::user() → User::find() → fires this
            // scope again → infinite recursion. Use hasUser() for User only: it returns
            // true only when the user object is already cached (no DB lookup needed).
            // For every other model Auth::check() is safe and more reliably true.
            $isUserModel = is_a($builder->getModel(), \App\Models\User::class);
            if ($isUserModel ? !Auth::hasUser() : !Auth::check()) {
                return;
            }

            // API routes have their own per-user access control (staff_id, user_id filters).
            // Applying the admin scope on API requests would break the mobile app because
            // records created by an admin (admin_id set) would become invisible to guards.
            if (request()->is('api/*')) {
                return;
            }

            /** @var \App\Models\User $user */
            $user = Auth::user();

            // Determine which admin dataset this user should read from.
            $ownerAdminId = null;
            if ($user->hasRole('admin')) {
                $ownerAdminId = (int) $user->id;
            } elseif (!$user->hasRole('superadmin') && !$user->hasRole('security_staff') && !is_null($user->admin_id)) {
                $ownerAdminId = (int) $user->admin_id;
            }

            if (!is_null($ownerAdminId)) {
                $model = $builder->getModel();
                $table = $model->getTable();

                // Visible-name exceptions are allowed only for admin 8766 and their users.
                $canUseVisibleNames = ($ownerAdminId === 8766);

                // Users: owner admin rows (+ optional curated system-level rows for admin 8766).
                if ($model instanceof \App\Models\User) {
                    if (!$canUseVisibleNames) {
                        $builder->where("{$table}.admin_id", $ownerAdminId);
                        return;
                    }

                    $entries = config('admin_visible_names', []);
                    $emails = [];
                    $names = [];
                    foreach ($entries as $e) {
                        $e = trim($e);
                        if ($e === '') continue;
                        if (strpos($e, '@') !== false) {
                            $emails[] = strtolower($e);
                        } else {
                            $parts = preg_split('/\s+/', $e);
                            if (count($parts) >= 1) {
                                $names[] = [strtolower($parts[0]), strtolower($parts[count($parts) - 1])];
                            }
                        }
                    }

                    $builder->where(function ($q) use ($table, $ownerAdminId, $emails, $names) {
                        $q->where("{$table}.admin_id", $ownerAdminId);

                        if (!empty($emails) || !empty($names)) {
                            $q->orWhere(function ($q2) use ($table, $emails, $names) {
                                $q2->whereNull("{$table}.admin_id")->where(function ($q3) use ($table, $emails, $names) {
                                    foreach ($emails as $mail) {
                                        $q3->orWhereRaw("LOWER({$table}.email) = ?", [$mail]);
                                    }
                                    foreach ($names as [$first, $last]) {
                                        $q3->orWhereRaw("(LOWER({$table}.first_name) = ? AND LOWER({$table}.last_name) = ?)", [$first, $last]);
                                    }
                                });
                            });
                        }
                    });

                    return;
                }

                // Employees: owner admin rows (+ optional curated system-level rows for admin 8766).
                if ($model instanceof \App\Models\Employee) {
                    if (!$canUseVisibleNames) {
                        $builder->where("{$table}.admin_id", $ownerAdminId);
                        return;
                    }

                    $entries = config('admin_visible_names', []);
                    $emails = [];
                    $names = [];
                    foreach ($entries as $e) {
                        $e = trim($e);
                        if ($e === '') continue;
                        if (strpos($e, '@') !== false) {
                            $emails[] = strtolower($e);
                        } else {
                            $parts = preg_split('/\s+/', $e);
                            if (count($parts) >= 1) {
                                $names[] = [strtolower($parts[0]), strtolower($parts[count($parts) - 1])];
                            }
                        }
                    }

                    $builder->where(function ($q) use ($table, $ownerAdminId, $emails, $names) {
                        $q->where("{$table}.admin_id", $ownerAdminId);

                        if (!empty($emails) || !empty($names)) {
                            $q->orWhere(function ($q2) use ($table, $emails, $names) {
                                $q2->whereNull("{$table}.admin_id")->where(function ($q3) use ($table, $emails, $names) {
                                    foreach ($emails as $mail) {
                                        $q3->orWhereRaw("LOWER({$table}.email) = ?", [$mail]);
                                    }
                                    foreach ($names as [$first, $last]) {
                                        $q3->orWhereRaw("(LOWER({$table}.fore_name) = ? AND LOWER({$table}.sur_name) = ?)", [$first, $last]);
                                    }
                                });
                            });
                        }
                    });

                    return;
                }

                // SiaCheckReport: owner admin rows OR rows belonging to employees of this admin.
                if ($model instanceof \App\Models\SiaCheckReport) {
                    $employeeIds = \App\Models\Employee::withoutGlobalScope('admin_scope')
                        ->where('admin_id', $ownerAdminId)
                        ->pluck('id')
                        ->toArray();

                    // Visible-name exceptions are allowed only for admin 8766 and their users.
                    $canUseVisibleNames = ($ownerAdminId === 8766);
                    $visibleEmployeeIds = [];
                    
                    if ($canUseVisibleNames) {
                        // Get employee IDs from admin_visible_names config
                        $visibleEmails = config('admin_visible_names', []);
                        if (!empty($visibleEmails)) {
                            $visibleEmployeeIds = \App\Models\Employee::withoutGlobalScope('admin_scope')
                                ->whereIn('email', $visibleEmails)
                                ->pluck('id')
                                ->toArray();
                        }
                    }

                    $builder->where(function ($query) use ($table, $ownerAdminId, $employeeIds, $visibleEmployeeIds) {
                        $query->where("{$table}.admin_id", $ownerAdminId)
                            ->orWhereIn("{$table}.employee_id", $employeeIds);

                        if (!empty($visibleEmployeeIds)) {
                            $query->orWhereIn("{$table}.employee_id", $visibleEmployeeIds);
                        }
                    });
                    return;
                }

                // Default for all other models (including shifts): owner admin rows only.
                $builder->where("{$table}.admin_id", $ownerAdminId);
                return;
            }

            // Superadmin and every other role sees only system-level records (admin_id IS NULL).
            $builder->whereNull($builder->getModel()->getTable() . '.admin_id');
        });
    }


    public static function resolveOwnerAdminId(): ?int
    {
        if (!Auth::check()) {
            return null;
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return (int) $user->id;
        }

        if (
            !$user->hasRole('superadmin') &&
            !$user->hasRole('security_staff') &&
            !is_null($user->admin_id)
        ) {
            return (int) $user->admin_id;
        }

        return null;
    }

    public static function cacheSuffix(): string
    {
        $ownerAdminId = static::resolveOwnerAdminId();

        return is_null($ownerAdminId)
            ? 'system'
            : 'admin_' . $ownerAdminId;
    }

    /**
     * Temporarily remove the admin scope so you can query freely.
     * Usage: Model::withoutAdminScope()->get();
     */
    public static function withoutAdminScope(): Builder
    {
        return static::withoutGlobalScope('admin_scope');
    }
}
