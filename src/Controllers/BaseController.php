<?php
namespace Alterindonesia\ServicePattern\Controllers;

use Alterindonesia\ServicePattern\Contracts\IServiceEloquent;
use Illuminate\Database\Eloquent\Collection;

class BaseController
{
    protected IServiceEloquent $service;

    protected function response($result): \Illuminate\Http\JsonResponse
    {
        if($result['httpCode'] >= 200 && $result['httpCode'] < 300) {
            return $this->responseSuccess($result);
        }
        return $this->responseError($result);
    }

    protected function responseSuccess($result) : \Illuminate\Http\JsonResponse
    {
        $responseData = null;
        if(isset($result['resource'])) {
            if($result['data'] instanceof Collection) {
                $responseData = $result['resource']::collection($result['data']);
            } else {
                $responseData = new $result['resource']($result['data']);
            }
            return response()->json([
                'message' => $result['messages'],
                'data' => $responseData
            ], $result['httpCode']);
        }
        return response()->json($result['data'], $result['httpCode']);
    }

    protected function responseError($result) : \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => $result['messages'] ?? 'Not Found',
            'data' => $result['data']
        ]);
    }

    public function index() : \Illuminate\Http\JsonResponse
    {
        $result = $this->service->index();
        return $this->response($result);
    }

    public function store() : \Illuminate\Http\JsonResponse
    {
        $result = $this->service->store(request()->all());
        return $this->response($result);
    }

    public function update($id) : \Illuminate\Http\JsonResponse
    {
        $result = $this->service->update($id, request()->all());
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
