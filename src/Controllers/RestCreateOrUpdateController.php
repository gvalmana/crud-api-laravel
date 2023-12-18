<?php

namespace CrudApiRestfull\Controllers;

use CrudApiRestfull\Repositories\CreateOrUpdateRepository;
use CrudApiRestfull\Traits\HttpResponsable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use CrudApiRestfull\Resources\Messages;
use Illuminate\Database\Eloquent\Model;
use CrudApiRestfull\Traits\PaginationTrait;
use CrudApiRestfull\Traits\ParamsProcessTrait;
use Exception;

class RestCreateOrUpdateController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, HttpResponsable, ParamsProcessTrait, PaginationTrait;

    /**
     * @var Model $modelClass
     */
    public Model $modelClass;

    /**
     * @var CreateOrUpdateRepository $repository
     */
    public CreateOrUpdateRepository $repository;
    public $apiResource;
    public $not_found_message = Messages::NOT_FOUND_MESSAGE;
    public $created_message = Messages::CREATED_SUCCESS_MESSAGE;
    public $updated_message = Messages::UPDATED_SUCCESS_MESSAGE;

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $params = $request->all();
            $result = $this->repository->create($params);
            if ($result['success']) {
                DB::commit();
                $response = $this->getResponseData($result);
                return $this->makeResponseCreated($response, $this->created_message);
            }
            DB::rollBack();
            return $this->makeResponseUnprosesableEntity($result['errors']);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->makeResponseException($e);
        }
    }

    public function update(Request $request, $id)
    {

        try {
            DB::beginTransaction();
            $params = $request->all();
            $result = $this->repository->update($id, $params);
            if ($result['success']) {
                DB::commit();
                return $this->makeResponseOK($this->apiResource::make($result['model']), $this->updated_message);
            } else {
                DB::rollBack();
                return $this->makeResponseUnprosesableEntity($result['errors']);
            }
        } catch (\Throwable $ex) {
            DB::rollBack();
            if ($ex instanceof ModelNotFoundException) {
                return $this->makeResponseNotFound($this->not_found_message);
            }
            return $this->makeResponseException($ex);
        }
    }

    private function getResponseData($result)
    {
        return isset($result['model'])
            ? $this->apiResource::make($result['model'])
            : (isset($result['models'])
                ? $this->apiResource::collection($result['models'])
                : []);
    }
}
