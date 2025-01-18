<?php
namespace Alterindonesia\ServicePattern\ServiceEloquents;

use Alterindonesia\ServicePattern\Contracts\IServiceEloquent;
use Alterindonesia\ServicePattern\Libraries\ServiceResponse;
use Alterindonesia\ServicePattern\Resources\AnonymousResource;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Spatie\QueryBuilder\QueryBuilder as SpatieQueryBuilder;

class BaseServiceEloquent implements IServiceEloquent
{
    protected ?string $model;
    protected ?string $resource;
    protected ?ServiceResponse $serviceResponse;

    protected bool $createdBy = true;
    protected bool $updatedBy = true;

    protected Model|QueryBuilder|SpatieQueryBuilder|EloquentBuilder|null $eloquentModel;
    protected Model|QueryBuilder|SpatieQueryBuilder|EloquentBuilder|null $originalModel;

    public string|JsonResource $responseResource;

    protected array $result = [
        'status' => true,
        'model' => null,
        'resource' => null,
        'data' => [],
        'messages' => "",
        'httpCode' => 200
    ];


    public function __construct() {
        $this->initializeModel($this->model);
        $this->originalModel = $this->eloquentModel;
        $this->serviceResponse = app(ServiceResponse::class);
        $this->resource = $this->getResource();
    }

    public function initializeModel($model):void
    {
        $router = app(Router::class);
        if($router->current()){
            if($router->current()->getActionMethod() === "store"){
                $this->eloquentModel = new $model();
            } else if($router->current()->getActionMethod() === "show" && $router->current()->parameter('id') !== null){
                $this->eloquentModel = (new $model)->where($router->current()->parameterNames[0] ?? 'id',$router->current()->parameter($router->current()->parameterNames[0]));
            } else if($router->current()->getActionMethod() === "update" && $router->current()->parameter('id') !== null){
                $this->eloquentModel = (new $model)->where($router->current()->parameterNames[0] ?? 'id',$router->current()->parameter($router->current()->parameterNames[0]));
            } else if($router->current()->getActionMethod() === "destroy" && $router->current()->parameter('id') !== null){
                $this->eloquentModel = (new $model)->where($router->current()->parameterNames[0] ?? 'id',$router->current()->parameter($router->current()->parameterNames[0]));
            } else {
                $this->eloquentModel = $model::query();
            }
        } else {
            $this->eloquentModel = new $model;
        }
    }

    /**
     * @return array|ServiceResponse
     */
    public function index() : array|ServiceResponse
    {
        $query = SpatieQueryBuilder::for($this->eloquentModel)
            ->allowedFilters($this->getDefaultAllowedFilters())
            ->allowedSorts($this->getDefaultAllowedSort());

        $query = $this->getDefaultWhere($query);
        $this->result['data'] = $query->paginate(request()->input('perPage') ?? 20);
        $this->result['messages'] = __("Data retrieved successfully");
        $this->serviceResponse->setData($this->result['data']);
        $this->serviceResponse->setMessage($this->result['messages']);
        $this->serviceResponse->setHttpCode($this->result['httpCode']);
        $this->serviceResponse->setResource($this->resource);
        return $this->serviceResponse;
    }

    /**
     * @param $query
     * @return Model|SpatieQueryBuilder|QueryBuilder|EloquentBuilder
     */
    public function onBeforeList($query): Model|SpatieQueryBuilder|QueryBuilder|EloquentBuilder
    {
        return $query;
    }

    /**
     * @param $id
     * @param $field
     * @return array|ServiceResponse
     */
    public function show($id, $field=null) : array|ServiceResponse
    {
        $first = $this->eloquentModel->first();
        if (!$first) {
            $this->result['status'] = false;
            $this->result['messages'] = __("Data not found");
            $this->result['httpCode'] = 404;
            return $this->result;
        }
        $this->eloquentModel = $this->onBeforeShow($this->eloquentModel);
        $this->result['data'] = $this->eloquentModel->first();
        $this->result['messages'] = __("Data retrieved successfully");
        $this->result['httpCode'] = 200;

        $this->serviceResponse->setData($this->result['data']);
        $this->serviceResponse->setMessage($this->result['messages']);
        $this->serviceResponse->setHttpCode($this->result['httpCode']);
        $this->serviceResponse->setResource($this->resource);
        return $this->serviceResponse;
    }

    /**
     * @param array $data
     * @return array|ServiceResponse
     */
    public function store(array $data) : array|ServiceResponse
    {
        $record = $this->appendCreatedBy($data);
        $this->eloquentModel = $this->eloquentModel->create($record);
        $this->serviceResponse->setData($this->eloquentModel);
        $this->onAfterCreate($this->eloquentModel, $record);
        $this->serviceResponse->setHttpCode(201);
        $this->serviceResponse->setResource($this->resource);
        return $this->serviceResponse;
    }

    /**
     * @param $id
     * @param  array $data
     * @return array|ServiceResponse
     */
    public function update($id, array $data) : array|ServiceResponse
    {
        $first = $this->model->first();
        if (!$first) {
            $this->result['status'] = false;
            $this->result['messages'] = __("Data not found");
            $this->result['httpCode'] = 404;
            return $this->result;
        }
        $record = $this->appendUpdatedBy($data);
        $this->eloquentModel->update($record);
        $this->onAfterUpdate($this->eloquentModel->first(), $record);
        $this->result['data'] = $this->eloquentModel->first();
        $this->result['messages'] = __("Data updated successfully");
        $this->result['httpCode'] = 200;
        $this->serviceResponse->setData($this->result['data']);
        $this->serviceResponse->setMessage($this->result['messages']);
        $this->serviceResponse->setHttpCode($this->result['httpCode']);
        $this->serviceResponse->setResource($this->resource);
        return $this->serviceResponse;
    }

    /**
     * @param  array  $data
     * @return array
     */
    public function getCreatedData(array $data): array {
        return $data;
    }

    public function getUpdatedData(array $data): array {
        return $data;
    }

    public function destroy($id) : array|ServiceResponse
    {
        $this->eloquentModel = $this->find($id);
        if (!$this->eloquentModel) {
            $this->result['status'] = false;
            $this->result['messages'] = __("Data not found");
            $this->result['httpCode'] = 404;
            return $this->result;
        }
        $this->eloquentModel->delete();
        $this->onAfterDelete($this->eloquentModel);
        $this->serviceResponse->setData($this->eloquentModel);
        $this->serviceResponse->setHttpCode(200);
        $this->serviceResponse->setResource($this->resource);
        return $this->serviceResponse;
    }

    protected function getTableName(): string {
        return $this->originalModel->getTable();
    }

    protected function appendCreatedBy($record): array {
        if(Schema::hasColumn($this->getTableName(), 'created_by') && $this->createdBy){
            $record['created_by'] = json_encode($this->getCreatedBy());
        }
        return $record;
    }

    protected function getCreatedBy(): array {
        return [
            'id' => $this->getUser()->token->sub,
            'name' => $this->getUser()->token->name,
            'email' => $this->getUser()->token->email,
        ];
    }

    public function appendUpdatedBy($record): array {
        if(Schema::hasColumn($this->getTableName(), 'updated_by') && $this->updatedBy){
            $record['updated_by'] = json_encode($this->getUpdatedBy());
        }
        return $record;
    }

    protected function getUpdatedBy(): array {
        return [
            'id' => $this->getUser()->token->sub,
            'name' => $this->getUser()->token->name,
            'email' => $this->getUser()->token->email,
        ];
    }

    /**
     * @param $id
     * @param null $field
     * @return Model|null
     */
    public function find($id, $field=null): Model|null
    {
        if($field === null) {
            $isUuid = preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', $id);
            return $isUuid ? $this->originalModel->where('uuid', $id)->first() : $this->originalModel->find($id);
        }
        return $this->originalModel->where($field, $id)->first();
    }

    public function validateModel($id, $field=null): array
    {
        $_model = $this->find($id, $field);
        if(!$_model){
            $this->result['status'] = false;
            $this->result['messages'] = __("Data not found");
            $this->result['httpCode'] = 404;
            return $this->result;
        }
        $this->result['status'] = true;
        $this->result['messages'] = __("Data found");
        $this->result['data'] = $_model;
        $this->result['httpCode'] = 404;
        return $this->result;
    }

    public function getUser(): object
    {
        return Auth::user();
    }

    public function onBeforeShow($query): Model|SpatieQueryBuilder|QueryBuilder|EloquentBuilder
    {
        return $query;
    }

    public function onBeforeCreate(array $data): array
    {
        return $data;
    }

    public function onAfterCreate(Model $model, array $data): void
    {
        // TODO: Implement onAfterCreate() method.
    }

    public function onBeforeUpdate(array $data): array
    {
        return $data;
    }

    public function onAfterUpdate(Model $model, array $data): void
    {

    }

    public function onBeforeDelete(Model $model): array
    {
        return [
            'status' => true,
            'data' => $model
        ];
    }

    public function onAfterDelete(Model $model): void
    {
        // TODO: Implement onAfterDelete() method.
    }

    public function getDefaultAllowedFilters(): array
    {
        return (new $this->model)->getFillable();

    }

    public function getDefaultAllowedSort(): array
    {
        return (new $this->model)->getFillable();

    }

    public function getDefaultWhere($query): QueryBuilder|SpatieQueryBuilder|EloquentBuilder
    {
        return $query;
    }

    public function getResource(): string|JsonResource
    {
        return $this->resource ?? AnonymousResource::class;
    }
}
