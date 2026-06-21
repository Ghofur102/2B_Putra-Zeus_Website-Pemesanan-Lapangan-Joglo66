<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Auth\UpdateTenantProfileRequest;
use App\Services\Tenant\Auth\TenantAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ProfileController extends Controller
{
    protected TenantAuthService $authService;

    public function __construct(TenantAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function show(): View
    {
        $user = Auth::guard('web')->user();
        return view('tenant.auth.profile.detail', ['user' => $user]);
    }

    public function update(UpdateTenantProfileRequest $request): RedirectResponse
    {
        $response = back();
        /** @var \App\Models\User $user */
        $user = Auth::guard('web')->user();

        try {
            $this->authService->updateProfile($user, $request->validated());
            $response = $response->with('success', 'Profil berhasil diperbarui');
        } catch (Throwable $e) {
            $response = $response->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }

        return $response;
    }
}
