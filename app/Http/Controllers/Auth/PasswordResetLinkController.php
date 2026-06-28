<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Services\PasswordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class PasswordResetLinkController extends Controller
{
    public function __construct(private readonly PasswordService $passwords) {}

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $status = $this->passwords->sendResetLink($request->string('email')->toString());

        return back()->with('success', $status);
    }
}
