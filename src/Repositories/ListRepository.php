<?php

namespace CrudApiRestfull\Repositories;

use CrudApiRestfull\Contracts\InterfaceListRepository;
use CrudApiRestfull\Models\RestModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * @property Model $modelClass
 */
abstract class ListRepository implements InterfaceListRepository
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
        if (isset($params["filter"])) {
            $query = $this->buildQueryFilters($query, $params["filter"]);
        }
        if (isset($params["attr"])) {
            $query = $this->eqAttr($query, $params['attr']);
        }
        if (isset($params['relations'])) {
            $query = $this->relations($query, $params['relations']);
        }
        if (isset($params['select'])) {
            $query = $query->select($params['select']);
        }
        if (isset($params['orderBy'])) {
            $query = $this->orderBy($query, $params['orderBy']);
        }
        if (isset($params['oper'])) {
            $query = $this->oper($query, $params['oper']);
        }

        if (isset($params['deleted'])) {
            $query = $this->withDeleted($query, $params['deleted']);
        }

        if (isset($params['pagination']))
            return $this->makePagination($query, $params['pagination']);

        return $query->get();
    }

    public function show($id, array|string $params)
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

    public function select2List($params)
    {
        $query = $this->modelClass->query();
        $result = [];
        if (isset($params["filter"])) {
            $query = $this->buildQueryFilters($query, $params["filter"]);
        }
        if (isset($params["attr"])) {
            $query = $this->eqAttr($query, $params['attr']);
        }
        if (isset($params['orderBy'])) {
            $query = $this->orderBy($query, $params['orderBy']);
        }
        if (isset($params['oper'])) {
            $query = $this->oper($query, $params['oper']);
        }
        $data = $query->get();
        foreach ($data as $key => $value) {
            $result[$key]['option'] = $value[$this->modelClass->getPrincipalAttribute()];
            $result[$key]['value'] = $value[$this->modelClass->getPrimaryKey()];
        }
        return $result;
    }

    protected function buildQueryFilters($query, string|array $filter)
    {
        if (is_string($filter)) {
            $filter = json_decode($filter, true);
        }
        if (is_array($filter)) {
            foreach ($filter as $field => $item) {
                if (is_array($item)) {
                    list($field, $condition, $value) = $item;
                    if (is_array($value)) {
                        if ($condition == "in") {
                            $query->whereIn($field, $value);
                        } elseif ($condition == "not in") {
                            $query->whereNotIn($field, $value);
                        }
                    } elseif (is_array($value) && count($value) == 2) {
                        if ($condition == 'between') {
                            $query->whereBetween($field, $value);
                        } elseif ($condition == 'not between') {
                            $query->whereNotBetween($field, $value);
                        }
                    } else if (is_array($field)) {
                        if (in_array($condition,['like','not like'])) {
                            $query->where(function ($query) use ($field, $condition, $value) {
                                foreach ($field as $row) {
                                    $query->orWhere($row, $condition, '%' . $value . '%');
                                }
                            });
                        }
                    } else {
                        $query->where($field, $condition, $value);
                    }
                } else {
                    $query->where($field, '=', $item);
                }
            }
        } else {
            Log::warning("Query Filter recibed with errors");
        }
        return $query;
    }

    protected function makePagination($query, string|array $pagination)
    {
        if (is_string($pagination))
            $pagination = json_decode($pagination, true);
        $currentPage = isset($pagination["page"]) ? $pagination["page"] : 1;
        $pageSize = isset($pagination["pageSize"]) ? $pagination["pageSize"] : $this->modelClass->perPage;
        return $query->paginate($pageSize, ['*'], 'page', $currentPage);
    }

    protected function relations($query, array|string $params)
    {
        if ($params == 'all' || array_search("all", $params) !== false)
            $query = $query->with($this->modelClass::RELATIONS);
        else
            $query = $query->with($params);
        return $query;
    }

    protected function eqAttr($query, array|string $params)
    {
        if (is_string($params)) {
            $params = json_decode($params);
        }
        foreach ($params as $index => $value) {
            if (is_array($value)) {
                $query = $query->whereIn($index, $value);
            } else
                $query = $query->where($index, $value);
        }
        return $query;
    }

    protected function orderBy($query, array|string $params)
    {
        foreach ($params as $elements) {
            if (is_string($elements)) {
                $elements = json_decode($elements, true);
            }
            foreach ($elements as $index => $value) {
                $query = $query->orderBy($index, $value);
            }
        }
        return $query;
    }

    protected function withDeleted($query, bool $params)
    {
        if ($params == true) {
            $query = $query->withTrashed();
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
}
