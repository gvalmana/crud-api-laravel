<?php
namespace CrudApiRestfull\Controllers;

use App\Services\Services;
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

class RestController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, HttpResponsable;

    protected $modelClass = "";

    /** @var Services $service */
    protected $service = "";

    protected $not_found_message = 'Modelo no encontrado';
    protected $created_message = 'Recurso creado correctamente';
    protected $updated_message = 'Recurso actualizado correctamente';
    /**
     * Display a listing of the resource.
     * @return []
     */
    protected function process_request(Request $request)
    {
        $parameters = [];
        array_key_exists('relations', $request->request->all()) ? $parameters['relations'] = $request['relations'] : $parameters['relations'] = null;
        array_key_exists('attr', $request->request->all()) ? $parameters['attr'] = $request['attr'] : $parameters['attr'] = null;
        array_key_exists('eq', $request->request->all()) ? $parameters['attr'] = $request['eq'] : false;
        array_key_exists('select', $request->request->all()) ? $parameters['select'] = $request['select'] : $parameters['select'] = "*";
        array_key_exists('pagination', $request->request->all()) ? $parameters['pagination'] = $request['pagination'] : $parameters['pagination'] = null;
        array_key_exists('orderby', $request->request->all()) ? $parameters['orderby'] = $request['orderby'] : $parameters['orderby'] = null;
        array_key_exists('oper', $request->request->all()) ? $parameters['oper'] = $request['oper'] : $parameters['oper'] = null;
        array_key_exists('deleted', $request->request->all()) ? $parameters['deleted'] = $request['deleted'] : $parameters['deleted'] = null;
        return $parameters;
    }

    public function index(Request $request)
    {
        $params = $this->process_request($request);
        $result = $this->service->list_all($params);
        if (count($result)==0) {
            return $this->makeResponseNoContent();
        }
        return $this->makeResponseList($result);
    }

    /**
     * Display a listing of the resource.
     * @return Json
     */
    public function validate_model(Request $request)
    {
        $params = $request->request->all();
        $scenario = isset($params['_scenario']) ? $params['_scenario'] : "create";
        $specific = isset($params["_specific"]) ? $params["_specific"] : false;
        $response = $this->service->validate_all($params, $scenario, $specific);
        if (!$response['success']) {
            return response($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        return $this->makeResponseOK($response);
    }

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

    public function update_multiple(Request $request)
    {
        DB::beginTransaction();
        try {
            $params = $request->all();
            $result = $this->service->update_multiple($params);
            if ($result['success'])
                DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        return $this->makeResponseOK($result);
    }

    public function show(Request $request, $id)
    {
        try {
            $params = $this->process_request($request);
            $result = $this->service->show($params, $id);
        } catch (\Throwable $exception) {
            if ($exception instanceof ModelNotFoundException) {
                return $this->makeResponseNotFound($this->not_found_message);
            }
        }
        return $this->makeResponseOK($result);
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
        return $this->makeResponseNoContent();
    }

    public function select2list(Request $request)
    {
        $params = $this->process_request($request);
        $result = $this->service->select2_list($params);
        if (count($result)==0) {
            return $this->makeResponseNoContent();
        }
        return $this->makeResponseOK($result);
    }    

}
