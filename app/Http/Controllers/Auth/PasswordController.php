<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ], [
            'current_password.required' => 'Kata sandi lama wajib diisi.',
            'current_password.current_password' => 'Kata sandi lama tidak sesuai.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Ketik ulang kata sandi baru tidak cocok.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $pesanSukses = 'Kata sandi keamanan akun Anda berhasil diperbarui.';
        $muatanToast = [
            'success' => true,
            'type' => 'success',
            'title' => 'Kata Sandi Diperbarui',
            'action' => 'UPDATE_PASSWORD',
            'status' => 'COMPLETED',
            'message' => $pesanSukses,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($request->expectsJson() || $request->ajax() || $request->header('X-Interaksi-Ajax') === '1') {
            $request->session()->flash('status', 'Kata sandi berhasil diperbarui.');
            $request->session()->flash('toast', $muatanToast);

            return response()->json($muatanToast);
        }

        return back()
            ->with('status', 'Kata sandi berhasil diperbarui.')
            ->with('toast', $muatanToast);
    }
}
