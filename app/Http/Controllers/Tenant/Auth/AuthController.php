<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Auth\TenantLoginRequest;
use App\Services\Tenant\Auth\TenantAuthService;
use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AuthController extends Controller
{
    private const ROUTE_LOGIN = 'login';
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

    protected TenantAuthService $authService;

    public function __construct(TenantAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function showLoginForm(): View
    {
        return view('tenant.auth.login');
    }

    public function login(TenantLoginRequest $request): RedirectResponse
    {
        $response = redirect()->route(self::ROUTE_LOGIN);

        try {
            $user = $this->authService->authenticate($request->validated());

            Auth::login($user, $request->remember_me ?? false);
            $request->session()->regenerate();

            if ($user->role === UserRole::TENANT->value) {
                $response = redirect()->route('tenant.booking.dashboard')->with(self::STATUS_SUCCESS, 'Login berhasil!');
            } else {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $response = redirect()->route(self::ROUTE_LOGIN)->with(self::STATUS_ERROR, 'Akses ditolak! Halaman ini khusus untuk Penyewa.');
            }
        } catch (Throwable $e) {
            $response = redirect()->route(self::ROUTE_LOGIN)->with(self::STATUS_ERROR, $e->getMessage());
        }

        return $response;
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        session()?->invalidate();
        session()?->regenerateToken();

        return redirect()->route(self::ROUTE_LOGIN)->with(self::STATUS_SUCCESS, 'Logout berhasil');
    }
}
