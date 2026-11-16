<?php

namespace App\Http\Controllers;

use App\Services\PencarianNavigasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PencarianController extends Controller
{
    /**
     * Endpoint pencarian global di topbar.
     * Hasil dibatasi sesuai peran pengguna yang sedang login.
     */
    public function __invoke(Request $request, PencarianNavigasiService $layananPencarian): JsonResponse
    {
        $kataKunci = (string) $request->query('q', '');
        $peran = $request->user()?->role;

        return response()->json([
            'kata_kunci' => $kataKunci,
            'peran' => $peran?->value,
            'hasil' => $layananPencarian->cari($kataKunci, $peran),
        ]);
    }
}
