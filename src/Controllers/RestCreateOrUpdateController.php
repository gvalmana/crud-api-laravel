<?php

namespace CrudApiRestfull\Controllers;

use CrudApiRestfull\Traits\HttpResponsable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Psy\Util\Json;
use Symfony\Component\HttpFoundation\Response;
use CrudApiRestfull\Resources\Messages;
use Illuminate\Database\Eloquent\Model;
use CrudApiRestfull\Services\Services;
use CrudApiRestfull\Services\ServicesCreateOrUpdate;
use CrudApiRestfull\Traits\PaginationTrait;
use CrudApiRestfull\Traits\ParamsProcessTrait;
use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RestCreateOrUpdateController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, HttpResponsable, ParamsProcessTrait, PaginationTrait;

    /**
     * @var Model $modelClass
     */
    public Model $modelClass;

    /**
     * @var ServicesCreateOrUpdate $service
     */
    public ServicesCreateOrUpdate $service;
    public $apiResource;
    public $not_found_message = Messages::NOT_FOUND_MESSAGE;
    public $created_message = Messages::CREATED_SUCCESS_MESSAGE;
    public $updated_message = Messages::UPDATED_SUCCESS_MESSAGE;

    /**
     * Display a listing of the resource.
     * @return Json
     */

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $params = $request->all();
            $result = $this->service->create($params);
            if ($result['success']) {
                DB::commit();
                $response = $this->getResponseData($result);
                return $this->makeResponseCreated($response, $this->created_message);
            } else {
                DB::rollBack();
                return $this->makeResponseUnprosesableEntity($result['errors']);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->makeResponseException($e);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $params = $request->all();
            $result = $this->service->update($params, $id);
            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            if ($exception instanceof ModelNotFoundException) {
                return $this->makeResponseNotFound($this->not_found_message);
            }
        }
        return $this->makeResponseOK($result, $this->updated_message);
    }

    private function getResponseData($result)
    {
        if (isset($result['model'])) {
            return $this->apiResource::make($result['model']);
        } elseif (isset($result['models'])) {
            return $this->apiResource::collection($result['models']);
        } else {
            return [];
        }
    }
}
