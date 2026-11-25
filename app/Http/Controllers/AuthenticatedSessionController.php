<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\PlainText;
use App\Rules\FilterEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function email(Request $request): JsonResponse
    {
        $validator = Validator::make([
            'email' => PlainText::clean($request->input('email')),
        ], [
            'email' => ['nullable', 'string', 'max:255', new FilterEmail],
        ]);

        $message = $validator->errors()->first('email');

        return response()->json([
            'message' => $message === '' ? null : $message,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => PlainText::clean($request->input('email')),
            'password' => PlainText::withoutNullBytes($request->input('password')),
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'string', 'max:255', new FilterEmail],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if ($user instanceof User && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
