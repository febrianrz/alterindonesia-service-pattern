<?php

namespace Alterindonesia\ServicePattern\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @mixin Model
 */
trait HasUuid
{
    protected string $uuidColumnName = 'uuid';

    protected static function bootHasUuid(): void
    {
        static::creating(static function (Model $model) {
            /**
             * @uses \App\Models\Traits\HasUuid::$uuidColumnName
             * @noinspection PhpUndefinedFieldInspection
             */
            $model->{$model->uuidColumnName} = Str::orderedUuid();
        });
    }
}
