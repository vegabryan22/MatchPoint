<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly AuthenticationService $authentication) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $this->authentication->login(
            $request->safe()->only(['email', 'password']),
            $request->boolean('remember'),
            $request,
        );

        return redirect()->intended(route('dashboard'))->with('success', 'Bienvenido a MatchPoint.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->authentication->logout($request);

        return redirect()->route('login')->with('success', 'Sesión cerrada correctamente.');
    }
}
