<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();

        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role) ?? UserRole::Supir;

        $route = $role->dashboardRoute();

        if (! Route::has($route)) {
            abort(403, 'Portal untuk role ini belum tersedia.');
        }

        return redirect()->route($route);
    }
}
