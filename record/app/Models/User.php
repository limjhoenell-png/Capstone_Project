<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_EMPLOYEE = 'employee';

    public const PAY_RATE_DAILY = 'daily';

    public const PAY_RATE_HOURLY = 'hourly';

    public const EMAIL_DOMAIN = 'biopaylog.com';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'full_name',
        'email',
        'password',
        'role',
        'position',
        'department',
        'contact_number',
        'employee_id',
        'employment_start_date',
        'pay_rate_type',
        'pay_rate',
        'working_days',
        'employee_rest_days',
        'monthly_incentive',
        'hours_per_day',
        'can_access_logbook',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'employment_start_date' => 'date',
            'password' => 'hashed',
            'pay_rate' => 'decimal:2',
            'working_days' => 'array',
            'employee_rest_days' => 'array',
            'monthly_incentive' => 'decimal:2',
            'hours_per_day' => 'decimal:2',
            'can_access_logbook' => 'boolean',
        ];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function payslipRequests(): HasMany
    {
        return $this->hasMany(PayslipRequest::class);
    }

    public function payrollDeductions(): HasMany
    {
        return $this->hasMany(EmployeePayrollDeduction::class)->orderBy('sort_order')->orderBy('id');
    }

    public function annualBenefits(): BelongsToMany
    {
        return $this->belongsToMany(AnnualBenefit::class)->withPivot('amount')->withTimestamps();
    }

    public function logbookEntries(): HasMany
    {
        return $this->hasMany(LogbookEntry::class);
    }

    public function notices(): HasMany
    {
        return $this->hasMany(UserNotice::class);
    }

    /**
     * Readable sign-in address from a person's name, such as hannah.panaligan@biopaylog.com.
     */
    public static function emailFromName(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::of($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->toString();

        if ($slug === '') {
            $slug = 'employee';
        }

        $slug = trim(substr($slug, 0, 64), '.');
        $candidate = $slug.'@'.self::EMAIL_DOMAIN;
        $suffix = 2;

        while (static::query()
            ->where('email', $candidate)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $candidate = $slug.'.'.$suffix.'@'.self::EMAIL_DOMAIN;
            $suffix++;
        }

        return $candidate;
    }

    public function departmentLabel(): string
    {
        $department = trim((string) ($this->department ?? ''));
        if ($department !== '') {
            return $department;
        }

        $position = trim((string) ($this->position ?? ''));

        return $position !== '' ? $position : 'Unassigned';
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isEmployee(): bool
    {
        return $this->role === self::ROLE_EMPLOYEE;
    }

    public function isPersonnel(): bool
    {
        return $this->isAdmin() || $this->isEmployee();
    }

    /**
     * @return list<string>
     */
    public static function personnelRoles(): array
    {
        return [self::ROLE_ADMIN, self::ROLE_EMPLOYEE];
    }

    public function scopePersonnel(Builder $query): Builder
    {
        return $query->whereIn($this->qualifyColumn('role'), self::personnelRoles());
    }

    public function canAccessLogbook(): bool
    {
        return $this->isAdmin() || (bool) $this->can_access_logbook;
    }

    /**
     * Values that may appear in legacy logbook fingerprint identifiers.
     * Includes int and string forms — legacy DBs often store VARCHAR IDs or leading zeros.
     *
     * @return list<int|string>
     */
    public function deviceMatchIds(): array
    {
        $candidates = [];

        $push = function ($value) use (&$candidates): void {
            if ($value === null || $value === '') {
                return;
            }

            $candidates[] = $value;

            if (is_numeric($value)) {
                $candidates[] = (int) $value;
                $candidates[] = (string) (int) $value;
            }

            $candidates[] = (string) $value;
        };

        $push($this->id);
        $push((int) $this->id);

        $external = $this->attributes['employee_id'] ?? $this->employee_id;
        $push($external);
        if (is_string($external) && $external !== '') {
            // Support IDs encoded with separators (e.g. 2026-00017).
            // Device tables often store only the trailing numeric part (17), not the full string.
            $digitGroups = [];
            if (preg_match_all('/\d+/', $external, $digitGroups) > 0) {
                $groups = $digitGroups[0] ?? [];

                if ($groups !== []) {
                    $last = (string) end($groups);
                    $push($last);
                    $trimmed = ltrim($last, '0');
                    if ($trimmed !== '') {
                        $push($trimmed);
                    }
                }

                $digitsOnly = implode('', $groups);
                if ($digitsOnly !== '') {
                    $push($digitsOnly);
                }
            }
        }

        $seen = [];
        $unique = [];

        foreach ($candidates as $id) {
            if ($id === null || $id === '') {
                continue;
            }

            $key = is_int($id) ? 'i:'.$id : 's:'.(string) $id;

            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $id;
            }
        }

        return array_values($unique);
    }
}
