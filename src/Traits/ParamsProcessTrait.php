<?php

namespace CrudApiRestfull\Traits;

use Illuminate\Http\Request;

/**
 *
 */
trait ParamsProcessTrait
{

    public $pagination = 20;
    public $page = 1;
    public $filter = [];
    public $orderBy = 'id';
    public $direction = 'ASC';
    public $parameters = [];

    protected function processParams(Request $request): void
    {
        if (isset($request->paginate)) {
            $this->pagination = $request->input('paginate', 20);
        } elseif (isset($request->pagination)) {
            $this->pagination = $request->input('pagination', 20);
        } elseif (isset($request->limit)) {
            $this->pagination = $request->input('limit', 20);
        }

        $this->page = $request->input('page', 1);
        if (gettype($request->input('filter')) == 'string') {
            $this->filter = $request->input('filter') ? json_decode($request->input('filter')) : [];
        } else {
            $this->filter = $request->input('filter') ? $request->input('filter') : [];
        }

        $this->filter = is_array($this->filter) ? $this->filter : [];

        $this->orderBy = $request->input('orderBy', 'created_at');
        $this->direction = $request->input('direction', 'ASC');
    }

    protected function processRequest(Request $request): array
    {
        $defaultParams = [
            'relations' => null,
            'attr' => null,
            'filter' => [],
            'select' => '*',
            'pagination' => $this->pagination,
            'page' => $this->page,
            'orderBy' => $this->orderBy,
            'direction' => $this->direction,
            'deleted' => false,
        ];
        return array_merge($defaultParams, $request->only(array_keys($defaultParams)));
    }

    protected function addFilter($key, $value, $operator = '='): void
    {
        $this->filter[] = compact('key', 'operator', 'value');
    }

    public function replaceFiltersAlias(array $alias)
    {
        $replaces = [];
        foreach ($this->filter as $field => $value) {
            if (is_array($value)) {
                $name = $value[0];
                if (isset($alias[$name])) {
                    $value[0] = $alias[$name];
                }
                $replaces[] = $value;
            } else {
                $name = $value[0];
                if (isset($alias[$name])) {
                    $name = $alias[$name];
                }
                $replaces[$name] = $value;
            }
        }

        $this->filter = $replaces;
    }

    public function removeFilters(array $skipFilters)
    {
        $filters = [];
        foreach ($this->filter as $field => $value) {
            if (is_array($value)) {
                $name = $value[0];
                if (!in_array($name, $skipFilters)) {
                    $filters[] = $value;
                }
            } else {
                $name = $value[0];
                if (!in_array($name, $skipFilters)) {
                    $filters[$name] = $value;
                }
            }
        }

        $this->filter = $filters;
    }

    public function hasInvalidFilters(array $validFilters): bool
    {

        foreach ($this->filter as $field => $value) {
            if (is_array($value)) {
                $name = $value[0];
                if (!in_array($name, $validFilters)) {
                    return true;
                }
            } else {
                $name = $value[0];
                if (!in_array($name, $validFilters)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function filterTransformValues($transformers)
    {
        foreach ($this->filter as $filter => &$val) {
            if (is_array($val)) {
                list($field, $condition, &$value) = $val;
                if (isset($transformers[$field])) {
                    $value = call_user_func($transformers[$field], $value);
                }
            } else {
                $val = call_user_func($transformers[$filter], $val);
            }
        }
    }

    protected function setOrderByTablePrefix(string $prefix)
    {
        $this->orderBy = "$prefix.$this->orderBy";
    }
}
