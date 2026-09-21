<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

/**
 * The authenticated user's own profile.
 *
 * This is intentionally NOT permission-gated: every signed-in user may manage
 * their own name, email and password. It is guarded by `auth` only.
 *
 * A user can NEVER change their own role, status or company here — the request
 * does not accept those fields, and this controller never touches them.
 */
class ProfileController extends Controller
{
    /** Show the profile page. */
    public function edit(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $user->load(['roles:id,name,label', 'company']);

        return view('settings.profile.edit', [
            'title' => 'User Profile',
            'user' => $user,
        ]);
    }

    /** Update name / email, and optionally the password. */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Only these three fields are ever written — never role/status/company.
        $user->name = $request->string('name')->toString();
        $user->email = $request->string('email')->toString();

        if ($request->wantsPasswordChange()) {
            $user->password = Hash::make($request->string('password')->toString());
        }

        $user->save();

        $message = $request->wantsPasswordChange()
            ? 'Profile and password updated.'
            : 'Profile updated.';

        return redirect()
            ->route('settings.profile.edit')
            ->with('success', $message);
    }
}
