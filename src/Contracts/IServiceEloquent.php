<?php

namespace Alterindonesia\ServicePattern\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

interface IServiceEloquent {
    public function index();
    public function show($id);
    public function store(FormRequest $request);
    public function update($id, FormRequest $request);
    public function destroy($id);
    public function getCreatedData(array $data): array;
    public function getUpdatedData(array $data): array;
    public function setUpdatedModel($id);

    // hook
    public function onBeforeCreate(array $data) : array;
    public function onAfterCreate(Model $model, array $data);
    public function onBeforeUpdate(array $data) : array;
    public function onAfterUpdate(Model $model, array $data);
    public function onBeforeDelete(Model $model) : Model;
    public function onAfterDelete(Model $model);

    public function getDefaultAllowedFilters(): array;
    public function getDefaultAllowedSort(): array;
}
