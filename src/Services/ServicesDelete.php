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
        if (!$this->modelClass->delete())
            $success = false;
        return compact('success', 'model');
    }

    public function destroyByIds(array $ids)
    {
        $models = $this->modelClass->query()->whereIn($this->modelClass->getPrimaryKey(), $ids)->get();
        $success = true;
        $failed = [];
        $deleted = [];
        foreach ($models as $row) {
            if (!$row->delete()) {
                $success = false;
                $faileds[] = $row;
            } else {
                $deleted[] = $row;
            }
        }
        return compact('success', 'deleted', 'failed');
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
        $models = $this->modelClass->query()->whereIn($this->modelClass->getPrimaryKey(), $ids)->withTrashed()->get();
        $success = true;
        $failed = [];
        $restored = [];
        foreach ($models as $row) {
            if (!$row->restore()) {
                $success = false;
                $faileds[] = $row;
            } else {
                $restored[] = $row;
            }
        }
        return compact('success', 'restored', 'failed');
    }
}
