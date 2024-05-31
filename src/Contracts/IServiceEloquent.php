<?php

namespace Alterindonesia\ServicePattern\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

interface IServiceEloquent {
    public function index();
    public function show($id);
    public function store(FormRequest $request);
    public function update($id, FormRequest $request);
    public function destroy($id, string $field);
    public function getCreatedData(array $data): array;
    public function getUpdatedData(array $data): array;

    // hook
    public function onBeforeCreate(array $data) : array;
    public function onAfterCreate(Model $model, array $data);
    public function onBeforeUpdate(array $data) : array;
    public function onAfterUpdate(Model $model, array $data) : void;
    public function onBeforeDelete(Model $model) : Model|Builder;
    public function onAfterDelete(Model $model): void;

    public function getDefaultAllowedFilters(): array;
    public function getDefaultAllowedSort(): array;
}
