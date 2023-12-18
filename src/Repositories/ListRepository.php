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

        $query = $this->buildQueryFilters($query, $params["filter"] ?? null);
        $query = $this->eqAttr($query, $params['attr'] ?? null);
        $query = $this->relations($query, $params['relations'] ?? null);
        $query = $query->select($params['select'] ?? null);
        $query = $this->orderBy($query, $params['orderBy'] ?? null);
        $query = $this->oper($query, $params['oper'] ?? null);
        $query = $this->withDeleted($query, $params['deleted'] ?? null);

        if (isset($params['pagination'])) {
            return $this->makePagination($query, $params['pagination']);
        }

        return $query->get();
    }

    public function show($id, array|string $params)
    {
        $query = $this->modelClass->query();

        $query = $this->applyRelations($query, $params);
        $query = $this->applySelect($query, $params);

        return $query->findOrFail($id);
    }

    private function applyRelations($query, $params)
    {
        if (isset($params['relations'])) {
            return $this->relations($query, $params['relations']);
        }
        return $query;
    }

    private function applySelect($query, $params)
    {
        if (isset($params['select'])) {
            return $query->select($params['select']);
        }
        return $query;
    }

    public function select2List($params)
    {
        $query = $this->modelClass->query();

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

        $result = $data->map(function ($value) {
            return [
                'option' => $value[$this->modelClass->getPrincipalAttribute()],
                'value' => $value[$this->modelClass->getPrimaryKey()]
            ];
        })->all();

        return $result;
    }

    protected function buildQueryFilters($query, string|array $filter)
    {
        if (is_string($filter)) {
            $filter = json_decode($filter, true);
        }
        if (is_array($filter)) {
            foreach ($filter as $item) {
                if (is_array($item)) {
                    [$field, $condition, $value] = $item;
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
                    } elseif (is_array($field)) {
                        if (in_array($condition, ['like', 'not like'])) {
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
            Log::warning("Query Filter received with errors");
        }
        return $query;
    }

    protected function makePagination($query, string|array $pagination)
    {
        $pagination = is_string($pagination) ? json_decode($pagination, true) : $pagination;
        $currentPage = $pagination["page"] ?? 1;
        $pageSize = $pagination["pageSize"] ?? $this->modelClass->perPage;
        return $query->paginate($pageSize, ['*'], 'page', $currentPage);
    }

    protected function relations($query, array|string $params)
    {
        if ($params === 'all' || in_array('all', (array)$params)) {
            $query = $query->with($this->modelClass::RELATIONS);
        } else {
            $query = $query->with((array)$params);
        }
        return $query;
    }

    protected function eqAttr($query, array|string $params)
    {
        $params = is_string($params) ? json_decode($params) : $params;

        foreach ($params as $index => $value) {
            $query = is_array($value) ? $query->whereIn($index, $value) : $query->where($index, $value);
        }

        return $query;
    }

    protected function orderBy($query, array|string $params)
    {
        if (is_string($params)) {
            $params = json_decode($params, true);
        }

        foreach ($params as $index => $value) {
            $query = $query->orderBy($index, $value);
        }

        return $query;
    }

    protected function withDeleted($query, bool $params)
    {
        return $params ? $query->withTrashed() : $query;
    }

    protected function oper($query, $params, $condition = "and")
    {
        if (is_string($params)) {
            $params = json_decode($params, true);
        }

        foreach ($params as $index => $parameter) {
            if ($index === "or" || $index === "and") {
                $condition = $index;
            }

            $where = $condition == "and" ? "where" : "orWhere";

            if (!is_numeric($index) && (array_key_exists("or", $parameter) || array_key_exists("and", $parameter)) || ($index === "or" || $index === "and")) {
                if (array_key_exists("or", $parameter)) {
                    $query = $this->oper($query, $parameter['or'], "or");
                } elseif (array_key_exists("and", $parameter)) {
                    $query = $this->oper($query, $parameter['and'], "and");
                } elseif ($index === "or" || $index === "and") {
                    $query = $this->oper($query, $parameter, $index);
                }
            } else {
                if (is_array($parameter) || str_contains($parameter, '|')) {
                    if (is_array($parameter)) {
                        $parameter = array_pop($parameter);
                    }

                    $oper = $this->process_oper($parameter);

                    $where = $this->getWhereClause($where, $oper);

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

    private function getWhereClause($where, $oper)
    {
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

        return $where;
    }

    protected function process_oper($value)
    {
        return explode("|", $value);
    }
}
