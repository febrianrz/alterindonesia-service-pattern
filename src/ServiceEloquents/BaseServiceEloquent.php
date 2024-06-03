<?php
namespace Alterindonesia\ServicePattern\ServiceEloquents;

use Alterindonesia\ServicePattern\Contracts\IServiceEloquent;
use Alterindonesia\ServicePattern\Controllers\AnnonymousResource;
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
    protected bool $createdBy = true;
    protected bool $updatedBy = true;

    protected Model|QueryBuilder|SpatieQueryBuilder|EloquentBuilder $model;
    protected Model|QueryBuilder|SpatieQueryBuilder|EloquentBuilder $originalModel;
    public JsonResource $resource;
    protected array $result = [
        'model' => null,
        'resource' => null,
        'data' => [],
        'messages' => "",
        'httpCode' => 200
    ];

    /**
     * BaseServiceEloquent constructor.
     * @param Model $model
     * @param JsonResource|null $resource
     */
    public function __construct(
        Model $model,
        JsonResource $resource=null
    ) {
        $router = app(Router::class);
        if($router->current()->getActionMethod() === "store"){
            $this->model = new $model();
        } else if($router->current()->getActionMethod() === "show" && $router->current()->parameter('id') !== null){
            $this->model = (new $model)->where($router->current()->parameterNames[0] ?? 'id',$router->current()->parameter($router->current()->parameterNames[0]));
        } else if($router->current()->getActionMethod() === "update" && $router->current()->parameter('id') !== null){
            $this->model = (new $model)->where($router->current()->parameterNames[0] ?? 'id',$router->current()->parameter($router->current()->parameterNames[0]));
        } else if($router->current()->getActionMethod() === "destroy" && $router->current()->parameter('id') !== null){
            $this->model = (new $model)->where($router->current()->parameterNames[0] ?? 'id',$router->current()->parameter($router->current()->parameterNames[0]));
        } else {
            $this->model = $model;
        }
        $this->originalModel = $model;
        $this->result['model'] = $model;
        $this->result['resource'] = $resource ?? null;
    }

    /**
     * @return array
     */
    public function index() : array
    {
        $query = SpatieQueryBuilder::for($this->model::query())
            ->allowedFilters($this->getDefaultAllowedFilters())
            ->allowedSorts($this->getDefaultAllowedSort());

        $query = $this->onBeforeList($query);
        $query = $this->getDefaultWhere($query);
        $this->result['data'] = $query->paginate(request()->input('perPage') ?? 20);
        $this->result['messages'] = __("Data retrieved successfully");
        return $this->result;
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
     * @return array
     */
    public function show($id, $field=null) : array
    {
        $first = $this->model->first();
        if (!$first) {
            $this->result['status'] = false;
            $this->result['messages'] = __("Data not found");
            $this->result['httpCode'] = 404;
            return $this->result;
        }
        $this->model = $this->onBeforeShow($this->model);
        $this->result['data'] = $this->model->first();
        $this->result['messages'] = __("Data retrieved successfully");
        return $this->result;
    }

    /**
     * @param array $data
     * @return array
     */
    public function store(array $data) : array
    {
        $record = $this->appendCreatedBy($data);
        $this->result['data'] = $this->model::create($record);
        $this->onAfterCreate($this->model, $record);
        $this->result['messages'] = __("Data created successfully");
        $this->result['httpCode'] = 201;
        return $this->result;
    }

    /**
     * @param $id
     * @param  array $data
     * @return array
     */
    public function update($id, array $data) : array
    {
        $first = $this->model->first();
        if (!$first) {
            $this->result['status'] = false;
            $this->result['messages'] = __("Data not found");
            $this->result['httpCode'] = 404;
            return $this->result;
        }
        $record = $this->appendUpdatedBy($data);
        $this->model->update($record);
        $this->onAfterUpdate($this->model->first(), $record);
        $this->result['data'] = $this->model->first();
        $this->result['messages'] = __("Data updated successfully");
        $this->result['httpCode'] = 200;
        return $this->result;
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

    public function destroy($id) : array
    {
        $first = $this->model->first();
        if (!$first) {
            $this->result['status'] = false;
            $this->result['messages'] = __("Data not found");
            $this->result['httpCode'] = 404;
            return $this->result;
        }
        $this->model->delete();
        $this->onAfterDelete($first);
        $this->result['data'] = [];
        $this->result['messages'] = __("Data deleted successfully");
        return $this->result;
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
            if($isUuid) {
                return $this->model::where('uuid', $id)->first();
            } else {
                return $this->model::find($id);
            }
        }
        return $this->model::where($field, $id)->first();
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
        return (new $this->model())->getFillable();
    }

    public function getDefaultAllowedSort(): array
    {
        return (new $this->model())->getFillable();
    }

    public function getDefaultWhere($query): QueryBuilder|SpatieQueryBuilder|EloquentBuilder
    {
        return $query;
    }

    public function getRequest(): string
    {
        return $this->request;
    }

    public function getResource(): string
    {
        return $this->resource;
    }
}
