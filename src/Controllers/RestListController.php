<?php
namespace CrudApiRestfull\Controllers;

use CrudApiRestfull\Contracts\InterfaceListServices;
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
use CrudApiRestfull\Services\ServicesList;
use CrudApiRestfull\Traits\PaginationTrait;
use CrudApiRestfull\Traits\ParamsProcessTrait;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RestListController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, HttpResponsable, ParamsProcessTrait, PaginationTrait;

    /**
     * @var Model $modelClass
     */
    public Model $modelClass;

    /**
     * @var ServicesList $service
     */
    public ServicesList $service;
    public $apiResource;
    public $not_found_message = Messages::NOT_FOUND_MESSAGE;
    public $created_message = Messages::CREATED_SUCCESS_MESSAGE;
    public $updated_message = Messages::UPDATED_SUCCESS_MESSAGE;
    public $restored_message = Messages::RESTORED_MESSAGE;
    public $deleted_message = Messages::DELETED_MESSAGE;

    public function __construct(InterfaceListServices $service)
    {
        $this->service =  $service;
    }

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

    public function select2list(Request $request)
    {
        $params = $this->processParams($request);
        $result = $this->service->select2List($params);
        if (count($result)==0) {
            return $this->makeResponseNoContent();
        }
        return $this->makeResponseOK($result);
    }
}
