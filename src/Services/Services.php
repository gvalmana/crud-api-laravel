<?php

namespace CrudApiRestfull\Services;


use CrudApiRestfull\Models\RestModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;

/**
 * @property Model $modelClass
 *
 * */
class Services
{

    /** @var RestModel|string $modelClass */
    public $modelClass = '';

    /**
     * Services constructor.
     * @param String $modelClass
     */
    public function __construct($modelClass)
    {
        $this->modelClass = new $modelClass;
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
        if(is_string($params)){
            $params=json_decode($params);
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

    public function process_oper($value)
    {
        return explode("|", $value);
    }

    public function list_all($params)
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
        if (isset($params['orderby'])) {
            $query = $this->order_by($query, $params['orderby']);
        }
        if (isset($params['oper'])) {
            $query = $this->oper($query, $params['oper']);
        }
        if (isset($params['pagination']))
            return $this->pagination($query, $params['pagination']);
        return $query->get()->jsonSerialize();
    }

    public function get_parents($modelClass, $scenario, $attributes = null, $specific = false)
    {
        $parent_array = [];
        if ($modelClass->hasHierarchy()) {
            $parent_class = $modelClass::PARENT['class'];
            $parent = new $parent_class();
            $parent->fill($attributes);
            $parent_validate = $parent->self_validate($scenario, $specific);
            if ($parent->hasHierarchy()) {
                $parent_array = $parent->get_parents($parent, $scenario, $attributes, $specific);
            }
            array_push($parent_array, $parent_validate);
        }
        return $parent_array;
    }


    public function save_parents($modelClass, $scenario, $attributes = null, $specific = false)
    {
        $parent = null;
        if ($modelClass->hasHierarchy()) {
            $parent_class = $modelClass::PARENT['class'];
            if (!isset($attributes[$this->modelClass->getPrimaryKey()])) {
                $parent = new $parent_class();
            } else {
                $parent = $parent_class::find($attributes[$this->modelClass->getPrimaryKey()]);
            }
            $parent->fill($attributes);
            $parent->save();
            if ($parent->hasHierarchy()) {
                $parent->save_parents($parent, $scenario, $attributes, $specific);
            }
        }
        return $parent;
    }

    protected function parents_validate($attributes, $scenario = null, $specific = false)
    {
        $result = null;
        $parents = $this->get_parents($this->modelClass, $scenario, $attributes, $specific);
        foreach ($parents as $p) {
            if (count($p['errors']) > 0) {
                $result['success'] = false;
                $result['errors'] = $p['errors'];
                $result['model'] = $p['model'];
            }
        }
        return $result;
    }

    public function validate_all(array $attributes, $scenario = 'create', $specific = false)
    {
        $validate = [];
        if (isset($attributes[$this->modelClass->getPrimaryKey()]) && $scenario == 'create')
            $scenario = "update";
        $this->modelClass->setScenario($scenario);
        if (count($this->modelClass::PARENT) > 0) {
            $parent_class = $this->modelClass::PARENT['class'];
            if (!isset($attributes[$this->modelClass->getPrimaryKey()])) {
                $parent = new $parent_class();
            } else {
                $parent = $parent_class::find($attributes[$this->modelClass->getPrimaryKey()]);
            }
            if (!$parent) {
                $result = ["success" => false, 'error' => "Element not found", "model" => $parent_class];
                return $result;
            }
            $validateparents = $this->parents_validate($attributes, $this->modelClass->getScenario(), $specific);
            if ($validateparents)
                $validate[] = $validateparents;
        }
        $this->modelClass->fill($attributes);
        $valid = $this->modelClass->self_validate($this->modelClass->getScenario(), $specific, false);
        if ($valid['success'] && count($validate) == 0) {
            $result = ["success" => true, 'error' => []];
        } else {
            if (!$valid['success'])
                array_push($validate, $valid);
            $result = ["success" => false, "errors" => $validate];
        }
        return $result;
    }

    public function save(array $attributes, $scenario = 'create')
    {
        $parent = null;
        if (isset($attributes[$this->modelClass->getPrimaryKey()])) {
            $this->modelClass = $this->modelClass::find($attributes[$this->modelClass->getPrimaryKey()]);
            $this->modelClass->setScenario('update');
        }
        $validate_all = $this->validate_all($attributes, $this->modelClass->getScenario());
        if (!$validate_all['success'])
            return $validate_all;
        if (count($this->modelClass::PARENT) > 0) {
            $parent = $this->save_parents($this->modelClass, $this->modelClass->getScenario(), $attributes);
        }
        if ($parent)
            $attributes[$this->modelClass->getPrimaryKey()] = $parent[$this->modelClass->getPrimaryKey()];
        $this->modelClass->fill($attributes);
        $this->modelClass->save();
        $result = ["success" => true, "model" => $this->modelClass->getAttributes()];
        return $result;
    }

    public function create(array $params)
    {
        if (isset($params[$this->modelClass::MODEL]) || array_key_exists(0, $params)) {
            $result = $this->save_array($params[$this->modelClass::MODEL]);
        } else {
            $result = $this->save($params);
        }
        return $result;
    }

    public function save_array(array $attributes, $scenario = 'create')
    {
        $result = [];
        $result['success'] = true;
        foreach ($attributes as $index => $model) {
            $save = $this->save($model, $scenario);
            if (!$save['success']) {
                $result['success'] = false;
            }
            $result['models'][] = $save;
        }
        return $result;
    }

    public function update(array $attributes, $id)
    {
        $this->modelClass = $this->modelClass->query()->findOrFail($id);
        $this->modelClass->setScenario("update");
        $specific = isset($attributes["_specific"]) ? $attributes["_specific"] : false;
        $valid = $this->modelClass->self_validate($this->modelClass->getScenario(), $specific);
        if ($valid['success']) {
            $this->modelClass->fill($attributes);
            $this->modelClass->save();
            $result = ["success" => true, "model" => $this->modelClass->jsonSerialize()];
        } else {
            $result = $valid;
        }
        return $result;
    }

    public function update_multiple(array $params)
    {
        $result = [];
        $result['sucess'] = true;
        foreach ($params as $index => $item) {
            $id = $item[$this->modelClass->getPrimaryKey()];
            $res = $this->update($item, $id);
            $result["models"][] = $res;
            if (!$res['success'])
                $result['sucess'] = false;
        }
        return $result;
    }

    public function show($params, $id)
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
        $this->modelClass = $this->modelClass->query()->findOrFail($id);
        $result = [];
        $result['success'] = true;
        $result['model'] = $this->modelClass;
        if (!$this->modelClass->destroy($id))
            $result['success'] = false;
        return $result;
    }

    public function select2_list($params)
    {
        $query = $this->modelClass->query();
        $result = [];
        if (isset($params["attr"])) {
            $query->av = $this->eq_attr($query, $params['attr']);
        }
        if (isset($params['orderby'])) {
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
        return ['success'=>true, 'data'=>$result];
    }    
}


