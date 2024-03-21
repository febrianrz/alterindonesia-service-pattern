<?php
namespace Alterindonesia\ServicePattern\ServiceEloquents;

use Alterindonesia\ServicePattern\Contracts\IServiceEloquent;
use App\Http\ServicesEloquents\IResultInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class BaseServiceEloquent implements IServiceEloquent
{
    protected string $model;
    protected $updatedModel = null;
    protected $deletedModel = null;

    protected string $resource;
    protected string $request;
    protected $auth;

    protected array $result = [
        'model' => null,
        'resource' => null,
        'data' => [],
        'messages' => "",
        'httpCode' => 200
    ];

    public function __construct(
        string $model,
        string $resource=null,
        string $request=null
    ) {
        $this->model = $model;
        $this->resource = $resource;
        $this->request = $request;

        $this->result['model'] = $model;
        $this->result['resource'] = $resource;

        $this->auth = Auth::class;
    }

    public function index() : array
    {
        $this->result['data'] = $this->model::all();
        $this->result['messages'] = __("Data retrieved successfully");
        return $this->result;
    }

    public function show($id) : array
    {
        $this->result['data'] = $this->model::find($id);
        $this->result['messages'] = __("Data retrieved successfully");
        return $this->result;
    }

    public function store(array $data) : array
    {
        $record = $this->appendCreatedBy($this->getCreatedData());
        $record = $this->onBeforeCreate($record);
        $this->result['data'] = $this->model::create($record);
        $this->onAfterCreate($this->result['data'], $record);
        $this->result['messages'] = __("Data created successfully");
        $this->result['httpCode'] = 201;
        return $this->result;
    }

    public function update($id, array $data) : array
    {
        if($this->updatedModel === null) {
            $this->setUpdatedModel($id);
        }

        if(!$this->updatedModel) {
            $this->result['messages'] = __("Data not found");
            $this->result['data'] = null;
            $this->result['httpCode'] = 404;
            return $this->result;
        }

        $record = $this->appendUpdatedBy($this->getUpdatedData());
        $record = $this->onBeforeUpdate($record);
        $this->updatedModel->update($record);
        $this->onAfterUpdate($this->updatedModel, $record);
        $this->result['data'] = $this->updatedModel;
        $this->result['messages'] = __("Data updated successfully");
        $this->result['httpCode'] = 200;
        return $this->result;
    }

    public function getCreatedData(): array {
        if($this->request){
            $_request = app($this->request);
            return $_request->validated();
        }
        return [];
    }

    public function getUpdatedData(): array {
        if($this->request){
            $_request = app($this->request);
            return $_request->validated();
        }
        return [];
    }

    public function destroy($id) : array
    {
        if($this->deletedModel === null) {
            $this->setDeletedModel($id);
        }

        if(!$this->deletedModel) {
            $this->result['messages'] = __("Data not found");
            $this->result['data'] = null;
            $this->result['httpCode'] = 404;
            return $this->result;
        }

        $this->deletedModel = $this->onBeforeDelete($this->deletedModel);
        $this->deletedModel->delete();
        $this->onAfterDelete($this->deletedModel);
        $this->result['data'] = $this->deletedModel;
        $this->result['messages'] = __("Data deleted successfully");
        return $this->result;
    }

    public function setUpdatedModel($id): void {
        $this->updatedModel = $this->model::find($id);
    }

    public function setDeletedModel($id): void {
        $this->deletedModel = $this->model::find($id);
    }

    protected function getTableName(): string {
        if($this->model instanceof Model){
            return $this->model->getTable();
        } else {
            $obj = new $this->model();
            return $obj->getTable();
        }
    }

    protected function appendCreatedBy($record): array {
        if(Schema::hasColumn($this->getTableName(), 'created_by')){
            $record['created_by'] = json_encode($this->getCreatedBy());
        }
        return $record;
    }

    protected function getCreatedBy(): array {
        return [
            'id' => $this->auth::id(),
            'name' => $this->auth::user()->token->name,
            'email' => $this->auth::user()->token->email,
        ];
    }

    public function appendUpdatedBy($record): array {
        if(Schema::hasColumn($this->getTableName(), 'updated_by')){
            $record['updated_by'] = json_encode($this->getUpdatedBy());
        }
        return $record;
    }

    protected function getUpdatedBy(): array {
        return [
            'id' => $this->auth::id(),
            'name' => $this->auth::user()->token->name,
            'email' => $this->auth::user()->token->email,
        ];
    }

    public function onBeforeCreate(array $data): array
    {
        return $data;
    }

    public function onAfterCreate(Model $model, array $data)
    {
        // TODO: Implement onAfterCreate() method.
    }

    public function onBeforeUpdate(array $data): array
    {
        return $data;
    }

    public function onAfterUpdate(Model $model, array $data)
    {

    }

    public function onBeforeDelete(Model $model): Model
    {
        return $model;
    }

    public function onAfterDelete(Model $model)
    {
        // TODO: Implement onAfterDelete() method.
    }
}
