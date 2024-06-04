<?php
namespace Alterindonesia\ServicePattern\Controllers;

use Alterindonesia\ServicePattern\Contracts\IServiceEloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;

class BaseController
{
    protected IServiceEloquent $service;

    protected string $request;
    protected string $response;

    public function __construct(
        IServiceEloquent $service,
        string $request = AnnonymousFormRequest::class,
        string $response = AnnonymousResource::class
    ) {
        $this->service = $service;
        $this->request = $request;
        $this->response = $response;
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
        } else {
            if($result['data'] instanceof Collection || $result['data'] instanceof LengthAwarePaginator) {
                return app($this->response)::collection($result['data']);
            } else {
                $responseData = [
                    'message' => $result['messages'],
                    'data' => new $this->response($result['data'])
                ];
            }
        }
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

    public function store(Request $request) : JsonResponse | ResourceCollection
    {
        $_request = new $this->request();
        if(!$_request->authorize()){
            return response()->json([
                'message' => 'Unauthorized',
                'data' => []
            ], 401);
        }
        $validator = Validator::make($request->all(), $_request->rules());
        if($validator->fails()){
            return response()->json([
                'message' => $validator->errors(),
                'data' => []
            ], 400);
        }
        $result = $this->service->store($validator->validated());
        return $this->response($result);
    }

    public function update($id, Request $request) : JsonResponse | ResourceCollection
    {
        $_request = new $this->request();
        if(!$_request->authorize()){
            return response()->json([
                'message' => 'Unauthorized',
                'data' => []
            ], 401);
        }
        $validator = Validator::make($request->all(), $_request->rules());
        if($validator->fails()){
            return response()->json([
                'message' => $validator->errors(),
                'data' => []
            ], 400);
        }
        $result = $this->service->update($id, $validator->validated());
        return $this->response($result);
    }

    public function show($id) : JsonResponse | ResourceCollection
    {
        $result = $this->service->show($id);
        return $this->response($result);
    }

    public function destroy($id) : JsonResponse | ResourceCollection
    {
        $result = $this->service->destroy($id);
        return $this->response($result);
    }
}
