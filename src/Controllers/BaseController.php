<?php
namespace Alterindonesia\ServicePattern\Controllers;

use Alterindonesia\ServicePattern\Contracts\IServiceEloquent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use function PHPUnit\Framework\isInstanceOf;

class BaseController
{
    protected IServiceEloquent $service;

    protected string $request;

    protected string $resource;

    /**
     * @param  IServiceEloquent  $service
     * @param  Request  $request
     * @param  string|null  $resource
     */
    public function __construct(IServiceEloquent $service)
    {
        $this->service = $service;
    }

    protected function response($result): \Illuminate\Http\JsonResponse | ResourceCollection
    {
        if($result['httpCode'] >= 200 && $result['httpCode'] < 300) {
            return $this->responseSuccess($result);
        }
        return $this->responseError($result);
    }

    protected function responseSuccess($result) : \Illuminate\Http\JsonResponse | ResourceCollection
    {
        $responseData = null;
        if(isset($result['resource'])) {
            if($result['data'] instanceof Collection || $result['data'] instanceof LengthAwarePaginator) {
                return $result['resource']::collection($result['data']);
            } else {
                $responseData = [
                    'message' => $result['messages'],
                    'data' => new $result['resource']($result['data'])
                ];
            }
            return response()->json($responseData, $result['httpCode']);
        }
        $responseData = [
            'message' => $result['messages'],
            'data' => $result['data']
        ];
        return response()->json($responseData, $result['httpCode']);
    }

    protected function responseError($result) : \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => $result['messages'] ?? 'Not Found',
            'data' => $result['data']
        ], $result['httpCode']);
    }

    public function index() : \Illuminate\Http\JsonResponse | ResourceCollection
    {
        $result = $this->service->index();
        return $this->response($result);
    }

    public function store(Request $request) : \Illuminate\Http\JsonResponse
    {
        $result = $this->service->store(app($this->request) ?? $request);
        return $this->response($result);
    }

    public function update($id, FormRequest $request) : \Illuminate\Http\JsonResponse
    {
        $payload = $request->all();
        if($this->request) {
            $payload = app($this->request)->validated();
        }
        $result = $this->service->update($id, $payload);
        return $this->response($result);
    }

    public function show($id) : \Illuminate\Http\JsonResponse
    {
        $result = $this->service->show($id);
        return $this->response($result);
    }

    public function destroy($id) : \Illuminate\Http\JsonResponse
    {
        $result = $this->service->destroy($id);
        return $this->response($result);
    }
}
