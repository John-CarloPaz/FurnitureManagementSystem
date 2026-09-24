<?php

namespace App\Domain\Audit;

use Illuminate\Database\Eloquent\Model;

/** Bridges Eloquent lifecycle events for audited models to the recorder. */
class AuditObserver
{
    public function __construct(private readonly AuditRecorder $recorder) {}

    public function created(Model $model): void
    {
        $this->recorder->record($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->recorder->record($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->recorder->record($model, 'deleted');
    }
}
