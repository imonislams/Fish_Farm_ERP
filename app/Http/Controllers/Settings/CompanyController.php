<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateCompanyRequest;
use App\Models\Company;
use App\Services\Settings\CompanyService;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;

/**
 * Company settings (Version 1 — SINGLE COMPANY).
 *
 * Controller responsibilities (docs/ARCHITECTURE.md): handle the request,
 * authorize (via route middleware + FormRequest), call the service, return a
 * view or a redirect with a flash message.
 *
 * There is no company selector and no company_id in the request — "the company"
 * is resolved through CompanyContext. See docs/DATABASE.md §1.
 */
class CompanyController extends Controller
{
    public function __construct(
        private readonly CompanyService $companyService,
    ) {}

    /** Show the company settings form (Inertia/React). */
    public function edit(): \Inertia\Response
    {
        $company = CompanyContext::get();

        abort_if($company === null, 404, 'No company has been created yet. Run the seeder.');

        return \Inertia\Inertia::render('Settings/Company/Edit', [
            'title' => 'Company Settings',
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
                'phone' => $company->phone,
                'email' => $company->email,
                'address' => $company->address,
                'currency' => $company->currency,
                'timezone' => $company->timezone,
                'status' => $company->status,
                'logo_url' => $company->logo ? asset('storage/' . $company->logo) : null,
                'created_at' => $company->created_at?->format('d M Y'),
                'updated_at' => $company->updated_at?->diffForHumans(),
            ],
            'timezones' => $this->timezones(),
            'currencies' => $this->currencies(),
        ]);
    }

    /** Persist company settings. */
    public function update(UpdateCompanyRequest $request): RedirectResponse
    {
        $company = CompanyContext::get();

        abort_if($company === null, 404, 'No company has been created yet. Run the seeder.');

        $this->companyService->update(
            company: $company,
            data: $request->safe()->except(['logo', 'remove_logo']),
            logo: $request->file('logo'),
            removeLogo: $request->boolean('remove_logo'),
        );

        return redirect()
            ->route('settings.company.edit')
            ->with('success', 'Company settings updated.');
    }

    /**
     * Timezones for the select.
     * A curated list keeps the dropdown usable; the field still validates with
     * PHP's `timezone` rule, so any valid value is accepted.
     *
     * @return array<string, string>
     */
    private function timezones(): array
    {
        $zones = [
            'Asia/Dhaka',
            'Asia/Kolkata',
            'Asia/Karachi',
            'Asia/Colombo',
            'Asia/Kathmandu',
            'Asia/Yangon',
            'Asia/Bangkok',
            'Asia/Jakarta',
            'Asia/Dubai',
            'Asia/Singapore',
            'Asia/Tokyo',
            'Asia/Shanghai',
            'UTC',
            'Europe/London',
            'America/New_York',
            'Australia/Sydney',
        ];

        return collect($zones)->mapWithKeys(fn(string $z): array => [$z => $z])->all();
    }

    /**
     * Common currencies for the select.
     * `currency` validates as a 3-letter code, so others remain possible.
     *
     * @return array<string, string>
     */
    private function currencies(): array
    {
        return [
            'BDT' => 'BDT — Bangladeshi Taka (৳)',
            'INR' => 'INR — Indian Rupee (₹)',
            'USD' => 'USD — US Dollar ($)',
            'EUR' => 'EUR — Euro (€)',
            'GBP' => 'GBP — British Pound (£)',
        ];
    }
}
