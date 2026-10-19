<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCompanyProfileRequest;
use App\Models\CompanyProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    /**
     * Show the company details form.
     */
    public function edit(): View
    {
        return view('admin.company.edit', [
            'profile' => CompanyProfile::query()->first() ?? new CompanyProfile,
        ]);
    }

    /**
     * Save the company details.
     *
     * The profile is saved through the model instance so its saved event clears the cache.
     */
    public function update(UpdateCompanyProfileRequest $request): RedirectResponse
    {
        $profile = CompanyProfile::query()->first() ?? new CompanyProfile;

        $profile->fill($request->validated())->save();

        return redirect()
            ->route('admin.company.edit')
            ->with('status', 'Company details saved.');
    }
}
