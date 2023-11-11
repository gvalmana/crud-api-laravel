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
use CrudApiRestfull\Traits\PaginationTrait;
use CrudApiRestfull\Traits\ParamsProcessTrait;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RestController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, HttpResponsable, ParamsProcessTrait, PaginationTrait;

    /**
     * @var Model $modelClass
     */
    public Model $modelClass;

    /**
     * @var Services $service
     */
    public Services $service;
    public $apiResource;
    public $not_found_message = Messages::NOT_FOUND_MESSAGE;
    public $created_message = Messages::CREATED_SUCCESS_MESSAGE;
    public $updated_message = Messages::UPDATED_SUCCESS_MESSAGE;
    public $restored_message = Messages::RESTORED_MESSAGE;
    public $deleted_message = Messages::DELETED_MESSAGE;

    /**
     * Display a listing of the resource.
     * @return []
     */
    public function index(Request $request)
    {
        $params = $this->processParams($request);
        $result = $this->service->listAll($params);
        $links = null;
        if ($result->count()==0) {
            return $this->makeResponseNoContent();
        }
        if (isset($request["pagination"])) {
            $links = $this->makeMetaData($result);
        }
        return $this->makeResponseList($this->apiResource::collection($result), $links);
    }

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
            if ($result['success']){
                DB::commit();
            } else {
                return $this->makeResponseUnprosesableEntity($result);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        return $this->makeResponseCreated($result, $this->created_message);
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

    public function show(Request $request, $id)
    {
        try {
            $params = $this->processParams($request);
            $result = $this->service->show($id, $params);
            return $this->makeResponseOK($this->apiResource::make($result));
        } catch (NotFoundHttpException $ex) {
            return $this->makeResponseNotFound();
        } catch (ModelNotFoundException $ex) {
            return $this->makeResponseNotFound();
        }

    }

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
        return $this->makeResponseOK($result, $this->deleted_message);
    }

    public function select2list(Request $request)
    {
        $params = $this->processParams($request);
        $result = $this->service->select2List($params);
        if (count($result)==0) {
            return $this->makeResponseNoContent();
        }
        return $this->makeResponseOK($result);
    }

    public function restore(Request $request, $id)
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

    public function updateMultiple(Request $request)
    {
        DB::beginTransaction();
        try {
            $params = $request->all();
            $result = $this->service->updateMultiple($params);
            if ($result['success'])
                DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        return $this->makeResponseOK($result);
    }

    public function validateModel(Request $request)
    {
        $params = $request->request->all();
        $scenario = isset($params["_scenario"]) ? $params["_scenario"] : "create";
        $specific = isset($params["_specific"]) ? $params["_specific"] : false;
        $response = $this->service->selfValidate($params, $scenario, $specific);
        if (!$response['success']) {
            return $this->makeResponseUnprosesableEntity($response['errors']);
        }
        return $this->makeResponseOK($response);
    }
}
