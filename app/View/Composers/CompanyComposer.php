<?php

namespace App\View\Composers;

use App\Support\CompanyContext;
use Illuminate\View\View;

/**
 * Shares company branding with every layout.
 *
 * This is what makes the company name appear consistently in the sidebar, the
 * header, the auth screens and the footer WITHOUT hard-coding it anywhere.
 *
 * Registered in AppServiceProvider::boot(). Uses CompanyContext, which caches
 * the lookup, so this costs one query per request at most.
 */
class CompanyComposer
{
    public function compose(View $view): void
    {
        $view->with('company', CompanyContext::get());
        $view->with('companyName', CompanyContext::name());
        $view->with('companyLogo', CompanyContext::logoUrl());
        $view->with('companyInitials', CompanyContext::initials());
    }
}
