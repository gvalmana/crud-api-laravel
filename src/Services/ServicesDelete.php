<?php

namespace CrudApiRestfull\Services;

use CrudApiRestfull\Contracts\InterfaceDeleteServices;
use CrudApiRestfull\Contracts\InterfaceServices;
use CrudApiRestfull\Models\RestModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;

/**
 * @property Model $modelClass
 */
abstract class ServicesDelete implements InterfaceDeleteServices
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
        if (!$this->modelClass->destroy($id))
            $success = false;
        return compact('success', 'model');
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
        return compact('success', 'deleteds', 'faileds');
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
        return compact('success', 'restored', 'faileds');
    }
}
