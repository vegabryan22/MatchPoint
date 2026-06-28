<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuditFilterRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\View\View;

final class AuditLogController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(AuditFilterRequest $request): View
    {
        return view('admin.audit.index', [
            'logs' => $this->audit->paginate($request->validated()),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
