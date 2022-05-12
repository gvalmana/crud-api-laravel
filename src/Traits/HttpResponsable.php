<?php
namespace CrudApiRestfull\Traits;

use Symfony\Component\HttpFoundation\Response;

/**
 * Para crear respuestas HTTP estandarizadas con diferentes codigos
 */
trait HttpResponsable
{
    public function makeResponse($success, $message, $status_code, $error = null)
    {
        $response['success'] = $success;
        $response['message'] = $message;
        if ($error != null) {
            $response['error'] = $error;
        }
        return response()->json($response, $status_code);
    }

    public function makeResponseOK($data, $message=null)
    {
        if ($this->checkMessage($message)) {
            $response['message'] = $message;
        }
        $response['success'] = true;
        $response['data'] = $data;
        return response()->json($response, Response::HTTP_OK);
    }

    public function makeResponseList($data)
    {
        return response()->json($data, Response::HTTP_OK);
    }

    public function makeResponseCreated($data, $message= null)
    {
        $response['message'] = $this->checkMessage($message)?$message:'Recurso creado correctamente.';
        $response['success'] = true;
        $response['data'] = $data;
        return response()->json($response, Response::HTTP_CREATED);
    }

    public function makeResponseUpdated($data, $message = null, $status_code = 200)
    {
        $response['message'] = $this->checkMessage($message)?$message:'Recurso actualizado correctamente.';
        $response['success'] =  true;
        $response['data'] = $data;
        return response()->json($response, $status_code);
    }

    public function makeResponseNotFound($message=null)
    {
        $response['message'] = $this->checkMessage($message)?$message:'Recurso no encontrado.';
        $response['success'] = false;
        return response()->json($response,Response::HTTP_NOT_FOUND);
    }

    public function makeResponseUnprosesableEntity($error, $message=null)
    {
        $response['message'] = $this->checkMessage($message)?$message:'Error en los datos.';
        $response['success'] = false;
        $response['data'] = $error;
        return response()->json($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function makeResponseNoContent($message=null)
    {
        $response['message'] = $message;
        return response()->json($response, Response::HTTP_NO_CONTENT);
    }

    protected function checkMessage($message)
    {
        if ($message != null) {
            return true;
        }
        return false;
    }
}
