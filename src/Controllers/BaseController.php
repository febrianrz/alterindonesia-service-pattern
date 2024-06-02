<?php
namespace Alterindonesia\ServicePattern\Controllers;

use Alterindonesia\ServicePattern\Contracts\IServiceEloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseController
{
    protected IServiceEloquent $service;

    protected string $request;

    protected string $resource;

    public function __construct(IServiceEloquent $service)
    {
        $this->service = $service;
    }

    protected function response($result): JsonResponse | ResourceCollection
    {
        if($result['httpCode'] >= 200 && $result['httpCode'] < 300) {
            return $this->responseSuccess($result);
        }
        return $this->responseError($result);
    }

    protected function responseSuccess($result) : JsonResponse | ResourceCollection
    {
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

    protected function responseError($result) : JsonResponse
    {
        return response()->json([
            'message' => $result['messages'] ?? 'Not Found',
            'data' => $result['data']
        ], $result['httpCode']);
    }

    public function index() : JsonResponse | ResourceCollection
    {
        $result = $this->service->index();
        return $this->response($result);
    }

    public function store(FormRequest|Request $request) : JsonResponse | ResourceCollection
    {
        $this->request = $this->request ?? $this->service->getRequest();
        $result = $this->service->store(app($this->request) ?? $request);
        return $this->response($result);
    }

    public function update($id, FormRequest|Request $request) : JsonResponse | ResourceCollection
    {
        $this->request = $this->request ?? $this->service->getRequest();
        $payload = $request->all();
        if($this->request) {
            $payload = app($this->request)->validated();
        }
        $result = $this->service->update($id, $payload);
        return $this->response($result);
    }

    public function show($id) : JsonResponse | ResourceCollection
    {
        $result = $this->service->show($id);
        return $this->response($result);
    }

    public function destroy($id) : JsonResponse | ResourceCollection
    {
        $result = $this->service->destroy($id, null);
        return $this->response($result);
    }
}
