<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

/**
 * BelongsToAdmin
 *
 * Attach this trait to any Eloquent model that should be scoped per admin user.
 *
 * Scoping rules (applied automatically via a global scope):
 *   - admin       → sees only records where admin_id = their own user ID
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

            if ($isAuth && Auth::user()->hasRole('admin')) {
                $model->admin_id = Auth::id();
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

            $user = Auth::user();

            if ($user->hasRole('admin')) {
                $model = $builder->getModel();
                $table = $model->getTable();

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

                // Users: admin sees admin-owned users OR system-level users matching
                // configured emails or first+last name pairs.
                if ($model instanceof \App\Models\User) {
                    $builder->where(function ($q) use ($table, $user, $emails, $names) {
                        $q->where("{$table}.admin_id", $user->id);

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

                // Employees: admin sees own admin_id rows OR system-level rows matching
                // the configured emails or first+last name pairs.
                if ($model instanceof \App\Models\Employee) {
                    $builder->where(function ($q) use ($table, $user, $emails, $names) {
                        $q->where("{$table}.admin_id", $user->id);

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

                // Default for other models: admin sees only their own admin_id rows
                $builder->where("{$table}.admin_id", $user->id);
                return;
            }

            // Superadmin and every other role sees only system-level records (admin_id IS NULL).
            $builder->whereNull($builder->getModel()->getTable() . '.admin_id');
        });
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
