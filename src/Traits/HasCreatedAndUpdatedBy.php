<?php

namespace Alterindonesia\ServicePattern\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/** @mixin Model */
trait HasCreatedAndUpdatedBy
{
    public static function bootHasCreatedAndUpdatedBy(): void
    {
        static::creating(static function (Model $model) {
            $user = Auth::user();

            if (! $user) {
                return;
            }

            $model->created_by ??= $user;
            $model->updated_by ??= $user;
        });

        static::updating(static function (Model $model) {
            $user = Auth::user();

            if (! $user) {
                return;
            }

            $model->updated_by ??= $user;
        });
    }
}
