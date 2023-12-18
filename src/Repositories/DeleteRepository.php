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
        $success = $model->delete();

        return compact('success', 'model');
    }

    public function destroyByIds(array $ids)
    {
        $models = $this->modelClass->query()->whereIn($this->modelClass->getPrimaryKey(), $ids)->get();
        $success = $models->delete();
        return compact('success', 'models');
    }

    public function restore($id)
    {
        $model = $this->modelClass->withTrashed()->findOrFail($id);
        $success = $model->restore();
        return compact('success', 'model');
    }

    public function restoreByIds(array $ids)
    {
        $models = $this->modelClass->query()
            ->whereIn($this->modelClass->getPrimaryKey(), $ids)
            ->withTrashed()
            ->get();

        $success = true;
        foreach ($models as $model) {
            $success = $model->restore();
        }

        return compact('success', 'models');
    }
}
