<?php

namespace Alterindonesia\ServicePattern\Contracts;

use Illuminate\Database\Eloquent\Model;

interface IServiceEloquent {
    public function index();
    public function show($id);
    public function store(array $data);
    public function update($id, array $data);
    public function destroy($id);
    public function getCreatedData(): array;
    public function getUpdatedData(): array;
    public function setUpdatedModel($id);

    // hook
    public function onBeforeCreate(array $data) : array;
    public function onAfterCreate(Model $model, array $data);
    public function onBeforeUpdate(array $data) : array;
    public function onAfterUpdate(Model $model, array $data);
    public function onBeforeDelete(Model $model) : Model;
    public function onAfterDelete(Model $model);
}
