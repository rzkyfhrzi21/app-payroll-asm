<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Pimpinan = 'pimpinan';
    case Supir = 'supir';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin HRD',
            self::Pimpinan => 'Pimpinan / Owner',
            self::Supir => 'Supir',
        };
    }

    /**
     * Peran yang boleh dibuat oleh peran tertentu.
     *
     * @return array<int, self>
     */
    public function peranYangBolehDibuat(): array
    {
        return match ($this) {
            self::Admin => [self::Admin, self::Supir],
            self::Pimpinan => [self::Admin, self::Pimpinan, self::Supir],
            self::Supir => [],
        };
    }

    public function bolehMembuat(self $peranTujuan): bool
    {
        return in_array($peranTujuan, $this->peranYangBolehDibuat(), true);
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Pimpinan => 'executive.dashboard',
            self::Supir => 'driver.dashboard',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function daftarLabel(): array
    {
        $hasil = [];

        foreach (self::cases() as $peran) {
            $hasil[$peran->value] = $peran->label();
        }

        return $hasil;
    }
}
