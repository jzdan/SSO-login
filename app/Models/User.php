<?php

namespace App\Models;

use App\Notifications\VerifyEmailAddress;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nip_lama',
        'nip_baru',
        'email',
        'email_bps',
        'password',
        'is_admin',
        'is_active',
        'last_login_at',
    ];

    /**
     * Default values, so freshly created (not yet refreshed) models behave like the DB defaults.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_admin' => false,
        'is_active' => true,
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
            'email_bps_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Kolom alamat email => kolom waktu verifikasinya.
     */
    public const EMAIL_COLUMNS = [
        'email' => 'email_verified_at',
        'email_bps' => 'email_bps_verified_at',
    ];

    public const BPS_EMAIL_DOMAIN = '@bps.go.id';

    /**
     * Tentukan kolom yang dipakai untuk login dari isian pengguna:
     * 9 digit = NIP lama, 12 digit = NIP baru, @bps.go.id = email BPS, selain itu = email Google.
     */
    public static function loginColumn(string $login): string
    {
        return match (true) {
            (bool) preg_match('/^\d{9}$/', $login) => 'nip_lama',
            (bool) preg_match('/^\d{12}$/', $login) => 'nip_baru',
            str_ends_with(Str::lower($login), self::BPS_EMAIL_DOMAIN) => 'email_bps',
            default => 'email',
        };
    }

    /**
     * Aturan validasi data identitas (dipakai di form admin, profil, dan pendaftaran).
     */
    public static function identityRules(?self $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nip_lama' => ['nullable', 'digits:9', Rule::unique('users')->ignore($user?->id)],
            'nip_baru' => ['nullable', 'digits:12', Rule::unique('users')->ignore($user?->id)],
            // Email Google tidak boleh @bps.go.id agar tidak tertukar dengan email BPS saat login.
            'email' => ['required', 'string', 'email', 'max:255', 'not_regex:/'.preg_quote(self::BPS_EMAIL_DOMAIN, '/').'$/i', Rule::unique('users')->ignore($user?->id)],
            'email_bps' => ['nullable', 'string', 'email', 'max:255', 'ends_with:'.self::BPS_EMAIL_DOMAIN, Rule::unique('users')->ignore($user?->id)],
        ];
    }

    /**
     * Rapikan isian sebelum validasi: email huruf kecil, spasi di NIP dibuang, isian kosong jadi null.
     */
    public static function normalizeIdentityInput(Request $request): void
    {
        $request->merge(collect(['nip_lama', 'nip_baru', 'email', 'email_bps'])
            ->filter(fn ($field) => $request->has($field))
            ->mapWithKeys(function ($field) use ($request) {
                $value = str_starts_with($field, 'nip')
                    ? preg_replace('/\s+/', '', (string) $request->input($field))
                    : Str::lower(trim((string) $request->input($field)));

                return [$field => $value === '' ? null : $value];
            })
            ->all());
    }

    public static function identityMessages(): array
    {
        return [
            'nip_lama.digits' => 'NIP lama harus 9 digit angka.',
            'nip_baru.digits' => 'NIP baru harus 12 digit angka.',
            'email.not_regex' => 'Email @bps.go.id diisi pada kolom Email BPS.',
            'email_bps.ends_with' => 'Email BPS harus berakhiran '.self::BPS_EMAIL_DOMAIN.'.',
        ];
    }

    public function emailIsVerified(string $column): bool
    {
        return $this->{$column} && $this->{self::EMAIL_COLUMNS[$column]};
    }

    public function hasAnyVerifiedEmail(): bool
    {
        return collect(array_keys(self::EMAIL_COLUMNS))->contains(fn ($column) => $this->emailIsVerified($column));
    }

    /**
     * Status yang ditampilkan: nonaktif (diblokir admin), belum verifikasi, atau aktif.
     */
    public function status(): string
    {
        return match (true) {
            ! $this->is_active => 'nonaktif',
            ! $this->hasAnyVerifiedEmail() => 'belum_verifikasi',
            default => 'aktif',
        };
    }

    /**
     * Kirim link verifikasi ke setiap alamat email yang belum terverifikasi.
     */
    public function sendEmailVerifications(): int
    {
        $columns = $this->unverifiedEmailColumns();

        foreach ($columns as $column) {
            Notification::route('mail', $this->{$column})->notify(new VerifyEmailAddress($this, $column));
        }

        return count($columns);
    }

    /**
     * Kolom email yang terisi tapi belum diverifikasi.
     *
     * @return list<string>
     */
    public function unverifiedEmailColumns(): array
    {
        return array_values(array_filter(
            array_keys(self::EMAIL_COLUMNS),
            fn ($column) => $this->{$column} && ! $this->emailIsVerified($column),
        ));
    }

    /**
     * Reset status verifikasi untuk alamat email yang baru diganti (panggil sebelum save).
     */
    public function resetChangedEmailVerification(): void
    {
        foreach (self::EMAIL_COLUMNS as $column => $verifiedAt) {
            if ($this->isDirty($column)) {
                $this->{$verifiedAt} = null;
            }
        }
    }

    public function loginActivities(): HasMany
    {
        return $this->hasMany(LoginActivity::class);
    }

    /**
     * Revoke every access & refresh token so that all connected apps are signed out.
     */
    public function revokeAllTokens(): void
    {
        $this->tokens()->with('refreshToken')->where('revoked', false)->each(function ($token) {
            $token->refreshToken?->revoke();
            $token->revoke();
        });
    }
}
