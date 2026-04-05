<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nexus\TenantOperations\Contracts\TenantOnboardingCoordinatorInterface;
use Nexus\TenantOperations\DTOs\TenantOnboardingRequest;
use Nexus\TenantOperations\Services\TenantReadinessChecker;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

final class TenantController extends Controller
{
    public function __construct(
        private readonly TenantOnboardingCoordinatorInterface $onboardingCoordinator,
        private readonly TenantReadinessChecker $readinessChecker
    ) {}

    public function onboardForm(): View
    {
        return view('tenants.onboard');
    }

    public function onboard(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'domain' => 'required|string|max:255',
            'plan' => 'required|string|in:starter,professional,enterprise',
        ]);

        $onboardingRequest = new TenantOnboardingRequest(
            tenantCode: $validated['code'],
            tenantName: $validated['name'],
            domain: $validated['domain'],
            adminEmail: $validated['email'],
            adminPassword: $validated['password'],
            plan: $validated['plan'],
        );

        $result = $this->onboardingCoordinator->onboard($onboardingRequest);

        if ($result->isSuccess()) {
            return redirect()->route('tenants.onboard.form')
                ->with('success', sprintf('Tenant successfully onboarded! ID: %s', $result->tenantId));
        }

        return back()->withInput()->withErrors(['onboarding' => $result->getMessage()]);
    }

    public function status(): View
    {
        $readiness = $this->readinessChecker->check();

        return view('tenants.status', [
            'ready' => $readiness['ready'],
            'issues' => $readiness['issues'],
        ]);
    }
}
