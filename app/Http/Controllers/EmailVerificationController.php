<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User && $user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/VerifyEmail');
    }

    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::query()->withoutGlobalScopes()->find($id);
        $known = $user instanceof User ? sha1($user->getEmailForVerification()) : '';

        if (! $user instanceof User || strlen($hash) !== strlen($known) || ! hash_equals($known, $hash)) {
            abort(403);
        }

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function send(Request $request): RedirectResponse
    {
        $user = $this->actor($request);

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('success', 'A new verification link was sent.');
    }
}
