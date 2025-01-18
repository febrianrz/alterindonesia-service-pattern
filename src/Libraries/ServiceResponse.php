<?php

namespace Alterindonesia\ServicePattern\Libraries;

use Alterindonesia\ServicePattern\Resources\AnonymousResource;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\QueryBuilder;

class ServiceResponse implements Arrayable
{
    private Collection|array|null|Model|LengthAwarePaginator $data;
    private int $httpCode;
    private string $message;
    private bool $_isError;

    private string $resource;

    public function __construct()
    {
        $this->data = collect();
        $this->httpCode = 200;
        $this->message = "";
        $this->resource = AnonymousResource::class;
        $this->_isError = false;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getMessage(): string
    {
        return $this->message === "" ? "Success" : $this->message;
    }

    public function getData(): Collection|Model|array|null|LengthAwarePaginator
    {
        return $this->data;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function toArray(): array
    {
        return $this->data->toArray();
    }

    public function setHttpCode(int $code): void
    {
        if ($this->message === "") {
            if ($code === 200) {
                $this->message = "Success";
            } else if ($code === 201) {
                $this->message = "Success";
            } else if ($code === 400) {
                $this->message = "Bad Request";
                $this->_isError = true;
            } else if ($code === 401) {
                $this->message = "Unauthorized";
                $this->_isError = true;
            } else if ($code === 403) {
                $this->message = "Forbidden";
                $this->_isError = true;
            } else if ($code === 404) {
                $this->message = "Not Found";
                $this->_isError = true;
            } else if ($code === 405) {
                $this->message = "Method Not Allowed";
                $this->_isError = true;
            } else if ($code === 422) {
                $this->message = "Unprocessable Entity";
                $this->_isError = true;
            } else if ($code === 500) {
                $this->message = "Internal Server Error";
                $this->_isError = true;
            } else if ($code === 503) {
                $this->message = "Service Unavailable";
                $this->_isError = true;
            } else {
                $this->message = "Unknown Error";
                $this->_isError = true;
            }
        }
        $this->httpCode = $code;
    }

    public function setData(array|null|Collection|Model|Builder|QueryBuilder|LengthAwarePaginator $data): void
    {
        $this->data = $data;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function setResource(string $resource): void
    {
        $this->resource = $resource;
    }

    public function isError(): bool
    {
        return $this->_isError;
    }

    public function isSuccess(): bool
    {
        return !$this->_isError;
    }
}
