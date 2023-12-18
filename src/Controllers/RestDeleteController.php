<?php

namespace CrudApiRestfull\Controllers;

use CrudApiRestfull\Contracts\InterfaceDeleteRepository;
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
use Symfony\Component\HttpFoundation\Response;
use Exception;

class RestDeleteController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, HttpResponsable, ParamsProcessTrait, PaginationTrait;

    /**
     * @var Model $modelClass
     */
    public Model $modelClass;

    /**
     * @var InterfaceDeleteRepository $repository
     */
    public InterfaceDeleteRepository $repository;
    public $apiResource;
    public $not_found_message = Messages::NOT_FOUND_MESSAGE;
    public $restored_message = Messages::RESTORED_MESSAGE;
    public $deleted_message = Messages::DELETED_MESSAGE;

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $result = $this->repository->destroy($id);
            DB::commit();

            return $this->makeResponseOK(
                $this->apiResource::make($result['model']),
                $this->deleted_message
            );
        } catch (\Throwable $exception) {
            DB::rollBack();
            if ($exception instanceof ModelNotFoundException) {
                return $this->makeResponseNotFound($this->not_found_message);
            }
        }
    }

    public function destroyByIds(Request $request)
    {

        try {
            DB::beginTransaction();
            $ids =  $request->input('ids', null);
            if ($ids) {
                $result = $this->repository->destroyByIds($ids);
            }
            DB::commit();
        } catch (Exception $ex) {
            DB::rollBack();
            return $this->makeResponseException($ex);
        }
        $success = $result['success'];
        unset($result["success"]);
        $data = $this->apiResource::collection($result['models']);
        return $this->makeResponse($success, $this->deleted_message, Response::HTTP_OK, $data);
    }

    public function restore($id)
    {
        try {
            $result = $this->repository->restore($id);
        } catch (\Throwable $exception) {
            if ($exception instanceof ModelNotFoundException) {
                return $this->makeResponseNotFound($this->not_found_message);
            }
        }

        $model = $this->apiResource::make($result['model']);
        return $this->makeResponseOK($model, $this->restored_message);
    }

    public function restoreByIds(Request $request)
    {
        $ids =  $request->input('ids', null);
        if (!$ids) {
            return $this->makeResponse(false, $this->restored_message, Response::HTTP_OK, []);
        }

        try {
            DB::beginTransaction();
            $result = $this->repository->restoreByIds($ids);
            DB::commit();
        } catch (Exception $ex) {
            DB::rollBack();
            return $this->makeResponseException($ex);
        }

        unset($result['success']);
        $data = $this->apiResource::collection($result['models']);
        return $this->makeResponse($result['success'], $this->restored_message, Response::HTTP_OK, $data);
    }
}
