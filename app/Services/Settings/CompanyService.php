<?php

namespace App\Services\Settings;

use App\Models\Company;
use App\Support\CompanyContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Company settings business logic.
 *
 * The controller stays thin and this service owns the write: the company row,
 * the logo file and cache invalidation happen together. See docs/ARCHITECTURE.md.
 *
 * Version 1 is SINGLE-COMPANY — the target company is passed in explicitly,
 * never read from user input.
 */
class CompanyService
{
    private const LOGO_DIRECTORY = 'company';

    /**
     * Update the company's details.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Company $company, array $data, ?UploadedFile $logo = null, bool $removeLogo = false): Company
    {
        return DB::transaction(function () use ($company, $data, $logo, $removeLogo): Company {
            if ($removeLogo && ! $logo) {
                $this->deleteLogo($company);
                $data['logo'] = null;
            }

            if ($logo) {
                $this->deleteLogo($company);
                // Stored on the public disk so asset('storage/...') can serve it.
                $data['logo'] = $logo->store(self::LOGO_DIRECTORY, 'public');
            }

            $company->fill(collect($data)->only([
                'name',
                'code',
                'phone',
                'email',
                'address',
                'currency',
                'timezone',
                'status',
            ])->all())->save();

            CompanyContext::forget();

            return $company->refresh();
        });
    }

    /** Remove the current logo file and clear the column. */
    private function deleteLogo(Company $company): void
    {
        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }
    }
}
