<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

final class AuditService
{
    private const SENSITIVE = ['password', 'remember_token'];

    public function __construct(private readonly AuditLogRepositoryInterface $auditLogs) {}

    public function record(
        string $action,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        ?int $userId = null,
    ): AuditLog {
        $request = request();

        return $this->auditLogs->create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
        ]);
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->auditLogs->paginate($filters);
    }

    public function prune(): int
    {
        return $this->auditLogs->pruneOlderThan(config('matchpoint.audit.retention_days'));
    }

    private function sanitize(array $values): array
    {
        return Arr::except($values, self::SENSITIVE);
    }
}
