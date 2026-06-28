<?php

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

final class AuditableObserver
{
    public function __construct(private readonly AuditService $audit) {}

    public function created(Model $model): void
    {
        $this->audit->record('created', $model, [], $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        $original = array_intersect_key($model->getRawOriginal(), $changes);

        $this->audit->record('updated', $model, $original, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->audit->record('deleted', $model, $model->getOriginal());
    }
}
