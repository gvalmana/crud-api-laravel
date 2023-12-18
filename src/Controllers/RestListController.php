<?php

namespace CrudApiRestfull\Controllers;

use CrudApiRestfull\Contracts\InterfaceListRepository;
use CrudApiRestfull\Contracts\InterfaceListServices;
use CrudApiRestfull\Repositories\ListRepository;
use CrudApiRestfull\Traits\HttpResponsable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use CrudApiRestfull\Resources\Messages;
use Illuminate\Database\Eloquent\Model;
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
     * @var InterfaceListRepository $repository
     */
    public ListRepository $repository;
    public $apiResource;
    public $not_found_message = Messages::NOT_FOUND_MESSAGE;
    public $created_message = Messages::CREATED_SUCCESS_MESSAGE;
    public $updated_message = Messages::UPDATED_SUCCESS_MESSAGE;
    public $restored_message = Messages::RESTORED_MESSAGE;
    public $deleted_message = Messages::DELETED_MESSAGE;

    public function __construct(InterfaceListRepository $repository)
    {
        $this->repository =  $repository;
    }

    /**
     * Display a listing of the resource.
     * @return []
     */
    public function index(Request $request)
    {
        $params = $this->processParams($request);
        $result = $this->repository->listAll($params);

        if ($result->isEmpty()) {
            return $this->makeResponseNoContent();
        }

        $links = null;

        if ($request->has("pagination")) {
            $links = $this->makeMetaData($result);
        }

        return $this->makeResponseList($this->apiResource::collection($result), $links);
    }

    public function show(Request $request, $id)
    {
        $params = $this->processParams($request);

        try {
            $result = $this->repository->show($id, $params);
            return $this->makeResponseOK($this->apiResource::make($result));
        } catch (NotFoundHttpException | ModelNotFoundException $ex) {
            return $this->makeResponseNotFound();
        }
    }

    public function select2list(Request $request)
    {
        $params = $this->processParams($request);
        $result = $this->repository->select2List($params);

        return count($result) == 0 ? $this->makeResponseNoContent() : $this->makeResponseOK($result);
    }
}
