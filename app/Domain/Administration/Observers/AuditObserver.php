<?php

namespace App\Domain\Administration\Observers;

use App\Domain\Administration\Services\ServiceAudit;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function __construct(private ServiceAudit $audit) {}

    public function created(Model $model): void
    {
        $this->audit->tracer('created', $model, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->audit->tracerChamps($model, $model->getOriginal(), $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->audit->tracer('deleted', $model);
    }
}