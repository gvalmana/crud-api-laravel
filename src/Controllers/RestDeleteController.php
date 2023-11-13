<?php
namespace CrudApiRestfull\Controllers;

use CrudApiRestfull\Contracts\InterfaceDeleteServices;
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
     * @var InterfaceDeleteServices $service
     */
    public InterfaceDeleteServices $service;
    public $apiResource;
    public $not_found_message = Messages::NOT_FOUND_MESSAGE;
    public $restored_message = Messages::RESTORED_MESSAGE;
    public $deleted_message = Messages::DELETED_MESSAGE;

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $result = $this->service->destroy($id);
            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            if ($exception instanceof ModelNotFoundException) {
                return $this->makeResponseNotFound($this->not_found_message);
            }
        }
        return $this->makeResponseOK($result['model'], $this->deleted_message);
    }

    public function restore($id)
    {
        try {
            $result = $this->service->restore($id);
        } catch (\Throwable $exception) {
            if ($exception instanceof ModelNotFoundException) {
                return $this->makeResponseNotFound($this->not_found_message);
            }
        }
        return $this->makeResponseOK($result, $this->restored_message);
    }

    public function destroyByIds(Request $request)
    {
        DB::beginTransaction();
        try {
            $ids =  $request->input('ids',null);
            if ($ids) {
                $result = $this->service->destroyByIds($ids);
            }
            DB::commit();
        } catch (Exception $ex) {
            DB::rollBack();
            return $this->makeResponseException($ex);
        }
        $success = $result['success'];
        unset($result["success"]);
        return $this->makeResponse($success, $this->deleted_message, Response::HTTP_OK, $result);
    }
    public function restoreByIds(Request $request)
    {
        DB::beginTransaction();
        try {
            $ids =  $request->input('ids',null);
            if ($ids) {
                $result = $this->service->restoreByIds($ids);
            }
            DB::commit();
        } catch (Exception $ex) {
            DB::rollBack();
            return $this->makeResponseException($ex);
        }
        $success = $result['success'];
        unset($result["success"]);
        return $this->makeResponse($success, $this->restored_message, Response::HTTP_OK, $result);
    }
}
