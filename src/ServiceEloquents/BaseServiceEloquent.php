<?php
namespace Alterindonesia\ServicePattern\ServiceEloquents;

use Alterindonesia\ServicePattern\Contracts\IServiceEloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Spatie\QueryBuilder\QueryBuilder as SpatieQueryBuilder;

class BaseServiceEloquent implements IServiceEloquent
{
    protected Model|QueryBuilder|SpatieQueryBuilder|EloquentBuilder $model;

    protected string $resource;
    protected string $request;

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
     * @param string|null $resource
     * @param string|null $request
     * @param mixed ...$args
     */
    public function __construct(
        Model $model,
        string $resource=null,
        string $request=null,
        array ...$args
    ) {
        $this->model = $model;
        $this->resource = $resource;
        $this->request = $request;

        $this->result['model'] = $model;
        $this->result['resource'] = $resource;

    }

    /**
     * @return array
     */
    public function index() : array
    {
        $query = SpatieQueryBuilder::for($this->model)
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
        $this->model = $this->find($id, $field);
        $this->model = $this->onBeforeShow($this->model);
        $this->result['data'] = $this->model;
        $this->result['messages'] = __("Data retrieved successfully");
        return $this->result;
    }

    /**
     * @param FormRequest|array $request
     * @return array
     */
    public function store(FormRequest|array $request) : array
    {
        if(is_array($request)){
            $payload = $request;
        } else {
            $payload = $request->validated();
        }

        $record = $this->appendCreatedBy($this->getCreatedData($payload));
        $record = $this->onBeforeCreate($record);
        $this->result['data'] = $this->model::create($record);
        $this->onAfterCreate($this->result['data'], $record);
        $this->result['messages'] = __("Data created successfully");
        $this->result['httpCode'] = 201;
        return $this->result;
    }

    /**
     * @param $id
     * @param  FormRequest|array  $request
     * @return array
     */
    public function update($id, FormRequest|array $request) : array
    {
        $result = $this->validateModel($id);
        if(!$result['status']){
            return $result;
        }
        $data = is_array($request) ? $request : $request->validated();
        $record = $this->appendUpdatedBy($this->getUpdatedData($data));
        $record = $this->onBeforeUpdate($record);
        $this->onAfterUpdate($this->model, $record);
        $this->result['data'] = $this->model;
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

    public function destroy($id,$field=null) : array
    {
        $result = $this->validateModel($id, $field);
        if(!$result['status']){
            return $result;
        }
        $result = $this->onBeforeDelete($this->model);
        if(!$result['status']){
            return [
                'status' => false,
                'messages' => __("Failed to delete data"),
                'httpCode' => 400
            ];
        }
        $this->model = $result['data'];
        $this->model->delete();
        $this->onAfterDelete($this->model);
        $this->result['data'] = $this->model;
        $this->result['messages'] = __("Data deleted successfully");
        return $this->result;
    }

    protected function getTableName(): string {
        return $this->model->getTable();
    }

    protected function appendCreatedBy($record): array {
        if(Schema::hasColumn($this->getTableName(), 'created_by')){
            $record['created_by'] = json_encode($this->getCreatedBy());
        }
        return $record;
    }

    protected function getCreatedBy(): array {
        return [
            'id' => Auth::user()->token->sub,
            'name' => Auth::user()->token->name,
            'email' => Auth::user()->token->email,
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
            'id' => Auth::user()->token->sub,
            'name' => Auth::user()->token->name,
            'email' => Auth::user()->token->email,
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
