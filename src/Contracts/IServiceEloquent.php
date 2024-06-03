<?php

namespace Alterindonesia\ServicePattern\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\QueryBuilder\QueryBuilder as SpatieQueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

interface IServiceEloquent {
    public function index() : array;
    public function show($id) : array;
    public function store(array $data) : array;
    public function update($id, array $data) : array;
    public function destroy($id) : array;
    public function getCreatedData(array $data): array;
    public function getUpdatedData(array $data): array;

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
