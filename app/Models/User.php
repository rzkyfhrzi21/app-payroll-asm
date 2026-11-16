<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Models\Concerns\NormalisasiHurufBesar;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'foto_profil', 'password', 'role', 'nomor_telepon', 'alamat'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, NormalisasiHurufBesar, Notifiable;

    /**
     * Email, password, dan role TIDAK boleh diubah kapitalisasinya.
     *
     * @var array<int, string>
     */
    protected array $hurufBesar = ['name', 'nomor_telepon', 'alamat'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }
    public function getUrlFotoProfilAttribute(): ?string
    {
        if (empty($this->foto_profil)) {
            return null;
        }
        if (str_starts_with($this->foto_profil, 'http://') || str_starts_with($this->foto_profil, 'https://')) {
            return $this->foto_profil;
        }
        $bersih = ltrim($this->foto_profil, '/');
        return str_starts_with($bersih, 'storage/') ? asset($bersih) : asset('storage/'.$bersih);
    }


    public function supir(): HasOne
    {
        return $this->hasOne(Karyawan::class, 'id_pengguna');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isPimpinan(): bool
    {
        return $this->role === UserRole::Pimpinan;
    }

    public function isSupir(): bool
    {
        return $this->role === UserRole::Supir;
    }
}
