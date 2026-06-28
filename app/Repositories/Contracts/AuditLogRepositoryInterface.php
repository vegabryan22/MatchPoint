<?php

namespace App\Repositories\Contracts;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuditLogRepositoryInterface
{
    /** @return LengthAwarePaginator<int, AuditLog> */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function create(array $attributes): AuditLog;

    public function pruneOlderThan(int $days): int;
}
