<?php

namespace Alterindonesia\ServicePattern\Controllers;

use Alterindonesia\ServicePattern\Contracts\IServiceEloquent;
use Alterindonesia\ServicePattern\Libraries\ServiceResponse;
use Alterindonesia\ServicePattern\Resources\AnonymousResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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

    protected function response($result): JsonResponse|ResourceCollection
    {
        if ($result instanceof ServiceResponse) {
            if($result->getHttpCode() < 300) {
                return $this->responseSuccess($result);
            } else {
                return $this->responseError($result);
            }
        }

        if ($result['httpCode'] >= 200 && $result['httpCode'] < 300) {
            return $this->responseSuccess($result);
        }
        return $this->responseError($result);
    }

    protected function responseSuccess($result): JsonResponse|ResourceCollection
    {
        if ($result instanceof ServiceResponse) {
            return $this->handleIfResponseIsServiceResponse($result);
        }
        return $this->handleIfResponseIsArray($result);
    }

    private function handleIfResponseIsArray($result): JsonResponse|ResourceCollection
    {
        if (isset($result['resource'])) {
            if ($result['data'] instanceof Collection || $result['data'] instanceof LengthAwarePaginator) {
                return $result['resource']::collection($result['data']);
            } else {
                $responseData = [
                    'message' => $result['messages'] ?? $result['message'] ?? 'Success',
                    'data'    => new $result['resource']($result['data'])
                ];
            }
        } else {
            if ($result['data'] instanceof Collection || $result['data'] instanceof LengthAwarePaginator) {
                return $this->response::collection($result['data']);
            } else {
                $responseData = [
                    'message' => $result['messages'] ?? $result['message'] ?? 'Success',
                    'data'    => new $this->response($result['data'])
                ];
            }
        }
        return response()->json($responseData, $result['httpCode']);
    }

    private function handleIfResponseIsServiceResponse(ServiceResponse $response): JsonResponse
    {
        $data = $response->getData();
        if($response->getResource() === AnonymousResource::class && $this->response !== AnonymousResource::class) {
            $resource = $this->response;
        } else {
            $resource = $response->getResource();
        }

        $responseData = [
            'status'  => $response->getHttpCode(),
            'message' => $response->getMessage(),
            'data'    => null
        ];

        // Handle empty data
        if (empty($data)) {
            return response()->json($responseData, $response->getHttpCode());
        }

        // Handle if data is Eloquent Model
        if ($data instanceof Model) {
            if ($resource !== "") {
                $responseData['data'] = new $resource($data);
            } else {
                $responseData['data'] = $data;
            }
            return response()->json($responseData, $response->getHttpCode());
        }

        // Handle collection
        if ($data->count() > 1) {
            $responseData['data'] = $resource::collection($data);
            return response()->json($responseData, $response->getHttpCode());
        }

        if ($data->isEmpty()) {
            return response()->json($responseData, $response->getHttpCode());
        }

        // Handle single item
        if ($resource !== "") {
            $responseData['data'] = new $resource($data->first());
        } else {
            $responseData['data'] = $data->first();
        }

        return response()->json($responseData, $response->getHttpCode());
    }

    protected function responseError($result): JsonResponse
    {
        return response()->json([
            'message' => $result['messages'] ?? $result['message'] ?? 'Not Found',
            'data'    => $result['data']
        ], $result['httpCode']);
    }

    public function index(): JsonResponse|ResourceCollection
    {
        $result = $this->service->index();
        return $this->response($result);
    }

    public function store(Request $request): JsonResponse|ResourceCollection
    {
        $_request = new $this->request();
        $_request->setMethod('POST');
        if (!$_request->authorize()) {
            return response()->json([
                'message' => 'Unauthorized',
                'data'    => []
            ], 401);
        }
        $validator = Validator::make($request->all(), $_request->rules());
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
                'data'    => []
            ], 400);
        }
        $result = $this->service->store($validator->validated());
        return $this->response($result);
    }

    public function update($id, Request $request): JsonResponse|ResourceCollection
    {
        $_request = new $this->request();
        $_request->setMethod('PUT');
        if (!$_request->authorize()) {
            return response()->json([
                'message' => 'Unauthorized',
                'data'    => []
            ], 401);
        }
        $validator = Validator::make($request->all(), $_request->rules());
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
                'data'    => []
            ], 400);
        }
        $result = $this->service->update($id, $validator->validated());
        return $this->response($result);
    }

    public function show($id): JsonResponse|ResourceCollection
    {
        $result = $this->service->show($id);
        return $this->response($result);
    }

    public function destroy($id): JsonResponse|ResourceCollection
    {
        $_request = new $this->request();
        $_request->setMethod('DELETE');
        if (!$_request->authorize()) {
            return response()->json([
                'message' => 'Unauthorized',
                'data'    => []
            ], 401);
        }
        $result = $this->service->destroy($id);
        return $this->response($result);
    }
}
