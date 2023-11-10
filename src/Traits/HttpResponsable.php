<?php

namespace CrudApiRestfull\Traits;

use Exception;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use CrudApiRestfull\Resources\Messages;

/**
 * Para crear respuestas HTTP estandarizadas con diferentes codigos
 */
trait HttpResponsable
{
    public function makeResponse(bool $success, string $message, int $status_code, $error = null)
    {
        $response['success'] = $success;
        $response['message'] = $message;
        $response['type'] = Messages::TYPE_SUCCESS;
        if ($error != null) {
            $response['error'] = $error;
            $response['type'] = Messages::TYPE_ERROR;
        }
        return response()->json($response, $status_code);
    }

    public function makeResponseOK($data = [], string $message = null)
    {
        $response['success'] = true;
        $response['type'] = Messages::TYPE_SUCCESS;
        if ($this->checkMessage($message)) {
            $response['message'] = $message;
        }
        $response['data'] = $data;
        return response()->json($response, Response::HTTP_OK);
    }

    public function makeResponseList($data, $links = null)
    {
        if (empty($links)) {
            $links = $this->makeMetaData($data);
        }
        $response['success'] = true;
        $response['type'] = Messages::TYPE_SUCCESS;
        $response['data'] = $data;
        $response['links'] = $links;
        return response()->json($response, Response::HTTP_OK);
    }

    public function makeResponseCreated($data, string $message = null)
    {
        $response['success'] = true;
        $response['type'] = Messages::TYPE_SUCCESS;
        $response['message'] = $this->checkMessage($message) ? $message : Messages::CREATED_SUCCESS_MESSAGE;
        $response['data'] = $data;
        return response()->json($response, Response::HTTP_CREATED);
    }

    public function makeResponseUpdated($data, string $message = null, int $status_code = Response::HTTP_OK)
    {
        $response['success'] =  true;
        $response['type'] = Messages::TYPE_SUCCESS;
        $response['data'] = $data;
        $response['message'] = $this->checkMessage($message) ? $message : Messages::UPDATED_SUCCESS_MESSAGE;
        return response()->json($response, $status_code);
    }

    public function makeResponseNotFound(string $message = null)
    {
        $response['success'] = false;
        $response['type'] = Messages::TYPE_SUCCESS;
        $response['data'] = null;
        $response['message'] = $this->checkMessage($message) ? $message : Messages::NOT_FOUND_MESSAGE;
        return response()->json($response, Response::HTTP_NOT_FOUND);
    }

    public function makeResponseUnprosesableEntity($error, string $message = null)
    {
        $response['success'] = false;
        $response['type'] = Messages::TYPE_ERROR;
        $response['errors'] = $error;
        $response['message'] = $this->checkMessage($message) ? $message : Messages::DATA_INVALID_MESSAGE;
        return response()->json($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function makeResponseNoContent(string $message = null)
    {
        $response['message'] = $this->checkMessage($message) ? $message : Messages::NOT_CONTENT_FOUND;
        $response["success"] = true;
        $response['type'] = Messages::TYPE_SUCCESS;
        $response["data"] = [];
        return response()->json($response, Response::HTTP_NO_CONTENT);
    }

    public function makeResponseError($message = null, $status_code = Response::HTTP_INTERNAL_SERVER_ERROR)
    {
        $response["success"] = false;
        $response['type'] = Messages::TYPE_ERROR;
        $response["message"] = $this->checkMessage($message) ? $message : Messages::SERVER_ERROR_MESSAGE;
        $response["data"] = [];
        return response()->json($response, $status_code);
    }

    public function makeResponseException(Exception $e)
    {
        $response["success"] = false;
        $response["error"] = $e->getMessage() . " " . $e->getLine() . " " . $e->getFile();
        $response["message"] = Messages::EXCEPTION_ERROR_MESSAGE;
        $response['type'] = Messages::TYPE_ERROR;
        return response()->json($response, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function makeResponseQueryException(QueryException $e)
    {
        $response["success"] = false;
        $response["error"] = $e->getMessage() . " " . $e->getLine() . " " . $e->getFile();
        $response["message"] = Messages::QUERY_ERROR_MESSAGE;
        $response['type'] = Messages::TYPE_ERROR;
        return response()->json($response, Response::HTTP_NOT_FOUND);
    }

    protected function checkMessage($message)
    {
        return isset($message);
    }
}
