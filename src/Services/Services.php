<?php

namespace CrudApiRestfull\Services;

use CrudApiRestfull\Contracts\InterfaceService;
use CrudApiRestfull\Models\RestModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;

/**
 * @property Model $modelClass
 *
 * */
class Services implements InterfaceService
{

    /** 
     * @var RestModel|string $modelClass
     */
    public $modelClass = '';

    /**
     * Services constructor.
     * @param String $modelClass
     */
    public function __construct(RestModel | string $modelClass = null)
    {
        if (isset($modelClass)) {
            $this->modelClass = new $modelClass;
        }
    }

    public function listAll(array $params)
    {
        $query = $this->modelClass->query();
        if (isset($params["attr"])) {
            $query->av = $this->eq_attr($query, $params['attr']);
        }
        if (isset($params['relations'])) {
            $query = $this->relations($query, $params['relations']);
        }
        if (isset($params['select'])) {
            $query = $query->select($params['select']);
        }
        if (isset($params['orderBy'])) {
            $query = $this->order_by($query, $params['orderby']);
        }
        if (isset($params['oper'])) {
            $query = $this->oper($query, $params['oper']);
        }

        if (isset($params['deleted'])) {
            $query = $this->with_deleted($query, $params['deleted']);
        }

        if (isset($params['pagination']))
            return $this->pagination($query, $params['pagination']);

        return $query->get();
    }

    public function selfValidate(array $attributes, $scenario = 'create', $specific = false)
    {
        $errors = [];
        if (isset($attributes[$this->modelClass->getPrimaryKey()]) && $scenario == 'create') {
            $scenario = "update";
        }
        $this->modelClass->setScenario($scenario);
        $this->modelClass->fill($attributes);
        $valid = $this->modelClass->self_validate($this->modelClass->getScenario(), $specific, false);
        $success = true;
        $errors = [];
        if (!$valid['success']) {
            $success = false;
            array_push($errors, $valid);
        }
        return compact($success, $errors);
    }

    public function create(array $params)
    {
        if (isset($params[$this->modelClass::MODEL]) || array_key_exists(0, $params)) {
            $result = $this->saveArray($params[$this->modelClass::MODEL]);
        } else {
            $result = $this->save($params);
        }
        return $result;
    }

    public function save(array $attributes, $scenario = 'create')
    {
        if (isset($attributes[$this->modelClass->getPrimaryKey()])) {
            $this->modelClass = $this->modelClass::find($attributes[$this->modelClass->getPrimaryKey()]);
            $this->modelClass->setScenario('update');
        }
        $validate_all = $this->selfValidate($attributes, $this->modelClass->getScenario());
        if (!$validate_all['success'])
            return $validate_all;
        $this->modelClass->fill($attributes);
        $this->modelClass->save();
        $success = true;
        $model = $this->modelClass->getAttributes();
        return compact($success, $model);
    }

    public function saveArray(array $attributes, $scenario = 'create')
    {
        $success = true;
        $models = [];
        foreach ($attributes as $index => $model) {
            $result = $this->save($model, $scenario);
            if (!$result['success']) {
                $success = false;
            }
            $models['models'][$index] = $result;
        }
        return compact($success, $models);
    }

    public function update($id, array $attributes)
    {
        $success = true;
        $errors = [];
        $this->modelClass = $this->modelClass->query()->findOrFail($id);
        $this->modelClass->setScenario("update");
        $specific = isset($attributes["_specific"]) ? $attributes["_specific"] : false;
        $valid = $this->modelClass->self_validate($this->modelClass->getScenario(), $specific);
        if (!$valid['success']) {
            $success = false;
            array_push($errors, $valid);
            return compact($success, $errors);
        } else {
            $this->modelClass->fill($attributes);
            $this->modelClass->save();
            $model = $this->modelClass;
        }
        return compact($success, $model);
    }

    public function updateMultiple(array $params)
    {
        $success = true;
        $models = [];
        foreach ($params as $index => $item) {
            $id = $item[$this->modelClass->getPrimaryKey()];
            $res = $this->update($item, $id);
            if (!$res['success']) {
                $success = false;
            }
            $models['models'][$index] = $res;
        }
        return compact($success, $models);
    }

    public function show($id, array $params)
    {
        $query = $this->modelClass->query();
        if (isset($params['relations'])) {
            $query = $this->relations($query, $params['relations']);
        }
        if (isset($params['select'])) {
            $query = $query->select($params['select']);
        }
        return $query->findOrFail($id);
    }

    public function destroy($id)
    {
        $model = $this->modelClass->query()->findOrFail($id);
        $success = true;
        if (!$this->modelClass->destroy($id))
            $success = false;
        return compact($success, $model);
    }

    public function destroyByIds(array $ids)
    {
        $models = $this->modelClass->query()->whereIn($this->modelClass->getPrimaryKey(), $ids);
        $success = true;
        $faileds = [];
        $deleteds = [];
        foreach ($models as $row) {
            if (!$row->destroy()) {
                $success = false;
                $faileds[] = $row->id;
            } else {
                $deleteds[] = $row->id;
            }
        }
        return compact($success, $deleteds, $faileds);
    }

    public function select2List($params)
    {
        $query = $this->modelClass->query();
        $result = [];
        if (isset($params["attr"])) {
            $query->av = $this->eq_attr($query, $params['attr']);
        }
        if (isset($params['orderBy'])) {
            $query = $this->order_by($query, $params['orderby']);
        }
        if (isset($params['oper'])) {
            $query = $this->oper($query, $params['oper']);
        }
        $data = $query->get();
        foreach ($data as $key => $value) {
            $result[$key]['value'] = $value[$this->modelClass->getPrimaryKey()];
            $result[$key]['text'] = $value[$this->modelClass->getPrincipalAttribute()];
        }
        return $result;
    }

    public function restore($id)
    {
        $model = $this->modelClass->withTrashed()->findOrFail($id);
        $success = true;
        if (!$model->restore()) {
            $success = false;
        }
        return compact($success, $model);
    }

    public function restoreByIds(array $ids)
    {
        $models = $this->modelClass->query()->whereIn($this->modelClass->getPrimaryKey(), $ids);
        $success = true;
        $faileds = [];
        $restored = [];
        foreach ($models as $row) {
            if (!$row->restore()) {
                $success = false;
                $faileds[] = $row->id;
            } else {
                $deleteds[] = $row->id;
            }
        }
        return compact($success, $restored, $faileds);
    }

    public function findBy(array $attributes)
    {
    }

    protected function pagination($query, $pagination)
    {
        if (is_string($pagination))
            $pagination = json_decode($pagination, true);
        $currentPage = isset($pagination["page"]) ? $pagination["page"] : 1;
        $pageSize = isset($pagination["pageSize"]) ? $pagination["pageSize"] : $this->modelClass->perPage;
        Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
        });
        return $query->paginate($pageSize);
    }

    protected function relations($query, $params)
    {
        if ($params == 'all' || array_search("all", $params) !== false)
            $query = $query->with($this->modelClass::RELATIONS);
        else
            $query = $query->with($params);
        return $query;
    }

    protected function eq_attr($query, $params)
    {
        if (is_string($params)) {
            $params = json_decode($params);
        }
        foreach ($params as $index => $parameter) {
            if (is_array($parameter)) {
                $query = $query->whereIn($index, $parameter);
            } else
                $query = $query->where([$index => $parameter]);
        }
        return $query;
    }

    protected function order_by($query, $params)
    {
        foreach ($params as $elements) {
            if (is_string($elements)) {
                $elements = json_decode($elements, true);
            }
            foreach ($elements as $index => $parameter) {
                $query = $query->orderBy($index, $parameter);
            }
        }
        return $query;
    }

    protected function oper($query, $params, $condition = "and")
    {
        if (is_string($params))
            $params = json_decode($params, true);
        foreach ($params as $index => $parameter) {
            if ($index === "or" || $index === "and")
                $condition = $index;
            $where = $condition == "and" ? "where" : "orWhere";
            if (!is_numeric($index) && (array_key_exists("or", $parameter) || array_key_exists("and", $parameter)) || ($index === "or" || $index === "and")) {
                if (array_key_exists("or", $parameter))
                    $query = $this->oper($query, $parameter['or'], "or");
                elseif (array_key_exists("and", $parameter))
                    $query = $this->oper($query, $parameter['and'], "and");
                elseif ($index === "or" || "and")
                    $query = $this->oper($query, $parameter, $index);
            } else {
                if (is_array($parameter) || str_contains($parameter, '|')) {
                    if (is_array($parameter))
                        $parameter = array_pop($parameter);
                    $oper = $this->process_oper($parameter);
                    if (array_search(strtolower("notbetween"), array_map('strtolower', $oper))) {
                        $where = $where . "NotBetween";
                    } elseif (array_search(strtolower("between"), array_map('strtolower', $oper))) {
                        $where = $where . "Between";
                    } elseif (array_search(strtolower("notin"), array_map('strtolower', $oper))) {
                        $where = $where . "NotIn";
                    } elseif (array_search(strtolower("in"), array_map('strtolower', $oper))) {
                        $where = $where . "In";
                    } elseif (array_search(strtolower("notnull"), array_map('strtolower', $oper))) {
                        $where = $where . "NotNull";
                    } elseif (array_search(strtolower("null"), array_map('strtolower', $oper))) {
                        $where = $where . "Null";
                    }
                    if (strpos($where, "etween") || strpos(strtolower($where), "in")) {
                        $oper[2] = [...$oper];
                        if (strpos(strtolower($where), "in")) {
                            unset($oper[2][0]);
                            unset($oper[2][1]);
                        }
                        unset($oper[3]);
                        unset($oper[1]);
                    }
                    if (strpos(strtolower($where), "null")) {
                        $oper = [$oper[0]];
                    }
                    $query = $query->$where(...$oper);
                }
            }
        }
        return $query;
    }

    protected function process_oper($value)
    {
        return explode("|", $value);
    }

    protected function with_deleted($query, $params)
    {
        if ($params == true) {
            $query = $query->withTrashed();
        }
        return $query;
    }
}
