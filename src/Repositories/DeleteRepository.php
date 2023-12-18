<?php

namespace CrudApiRestfull\Repositories;

use CrudApiRestfull\Contracts\InterfaceDeleteRepository;
use CrudApiRestfull\Models\RestModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;

/**
 * @property Model $modelClass
 */
abstract class DeleteRepository implements InterfaceDeleteRepository
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

    public function destroy($id)
    {
        $model = $this->modelClass->query()->findOrFail($id);
        $success = true;
        if (!$this->modelClass->delete())
            $success = false;
        return compact('success', 'model');
    }

    public function destroyByIds(array $ids)
    {
        $items = $this->modelClass->query()->whereIn($this->modelClass->getPrimaryKey(), $ids)->get();
        $success = true;
        $models = [];
        foreach ($items as $row) {
            $success = $row->delete();
            $models[] = $row;
        }
        return compact('success', 'models');
    }

    public function restore($id)
    {
        $model = $this->modelClass->withTrashed()->findOrFail($id);
        $success = true;
        if (!$model->restore()) {
            $success = false;
        }
        return compact('success', 'model');
    }

    public function restoreByIds(array $ids)
    {
        $items = $this->modelClass->query()->whereIn($this->modelClass->getPrimaryKey(), $ids)->withTrashed()->get();
        $success = true;
        $models = [];
        foreach ($items as $row) {
            $success = $row->restore();
            $models[] = $row;
        }
        return compact('success', 'models');
    }
}
