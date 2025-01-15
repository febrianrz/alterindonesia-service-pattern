<?php

namespace Alterindonesia\ServicePattern\Contracts;

use Alterindonesia\ServicePattern\Libraries\ServiceResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\QueryBuilder\QueryBuilder as SpatieQueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

interface IServiceEloquent {
    public function index() : array|ServiceResponse;
    public function show($id) : array|ServiceResponse;
    public function store(array $data) : array|ServiceResponse;
    public function update($id, array $data) : array|ServiceResponse;
    public function destroy($id) : array|ServiceResponse;
    public function getCreatedData(array $data): array|ServiceResponse;
    public function getUpdatedData(array $data): array|ServiceResponse;

    // hook
    public function onBeforeShow($query) : Model|SpatieQueryBuilder|QueryBuilder|EloquentBuilder;
    public function onBeforeList($query) : Model|SpatieQueryBuilder|QueryBuilder|EloquentBuilder;
    public function onBeforeCreate(array $data) : array;
    public function onAfterCreate(Model $model, array $data) : void;
    public function onBeforeUpdate(array $data) : array;
    public function onAfterUpdate(Model $model, array $data) : void;
    public function onBeforeDelete(Model $model) : array;
    public function onAfterDelete(Model $model): void;
    public function getDefaultAllowedFilters(): array;
    public function getDefaultAllowedSort(): array;
}
