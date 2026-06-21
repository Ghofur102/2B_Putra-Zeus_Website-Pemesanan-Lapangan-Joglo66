<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Auth\TenantRegisterRequest;
use App\Services\Tenant\Auth\TenantAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Throwable;

class RegisterController extends Controller
{
    protected TenantAuthService $authService;

    public function __construct(TenantAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function showRegistrationForm(): View
    {
        return view('tenant.auth.register');
    }

    public function register(TenantRegisterRequest $request): RedirectResponse
    {
        $response = redirect()->route('tenant.booking.dashboard');

        try {
            $user = $this->authService->registerTenant($request->validated());

            Auth::login($user);

            $response = $response->with('success', 'Registrasi berhasil! Selamat datang.');
        } catch (Throwable $e) {
            $response = back()->withInput()->with('error', 'Terjadi kesalahan saat mendaftar: ' . $e->getMessage());
        }

        return $response;
    }
}
