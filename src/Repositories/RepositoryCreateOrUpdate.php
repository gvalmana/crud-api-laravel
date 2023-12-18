<?php

namespace CrudApiRestfull\Repositories;

use CrudApiRestfull\Contracts\InterfaceUpdateOrCreateRepository;
use CrudApiRestfull\Models\RestModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * @property Model $modelClass
 */
abstract class CreateOrUpdateRepository implements InterfaceUpdateOrCreateRepository
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
            $this->modelClass = $modelClass;
        }
    }

    public function selfValidate(array $attributes, $scenario = 'create', $specific = false)
    {
        if (isset($attributes[$this->modelClass->getPrimaryKey()]) && $scenario === 'create') {
            $scenario = 'update';
        }

        $this->modelClass->setScenario($scenario);
        $this->modelClass->fill($attributes);

        return $this->modelClass->selfValidate($this->modelClass->getScenario(), $specific, false);
    }

    public function create(array $params)
    {
        $result = isset($params['data']) && is_array($params['data'])
            ? $this->saveArray($params['data'])
            : $this->save($params);

        return $result;
    }

    public function save(array $attributes, $scenario = 'create')
    {
        $model = $this->initializeModel($attributes, $scenario);

        $validateResult = $this->selfValidate($attributes, $model->getScenario());
        $success = $validateResult['success'];
        $errors = $validateResult['errors'];

        if ($success) {
            $model->fill($attributes);
            $model->save();
        }

        return compact('success', 'errors', 'model');
    }

    public function saveArray(array $attributes, $scenario = 'create')
    {
        $models = [];
        $errors = [];

        foreach ($attributes as $key => $data) {
            $result = $this->save($data, $scenario);

            if (!$result['success']) {
                $errors[$key][] = $result['errors'];
            }

            $models[] = $result['model'];
        }

        $success = empty($errors);

        return compact('success', 'models', 'errors');
    }

    public function update($id, array $attributes)
    {
        $model = $this->modelClass::findOrFail($id);
        $model->setScenario(RestModel::UPDATE_SCENARIO);

        $specific = $attributes["_specific"] ?? false;
        $validation = $this->selfValidate($attributes, $model->getScenario(), $specific);

        if (!$validation['success']) {
            return $validation;
        }

        $model->save();

        return [
            'success' => true,
            'errors' => null,
            'model' => $model,
        ];
    }

    public function updateMultiple(array $params)
    {
        $success = true;
        $models = [];

        foreach ($params as $index => $item) {
            $id = $item[$this->modelClass->getPrimaryKey()];
            $res = $this->update($item, $id);
            $success = $success && $res['success'];
            $models[] = $res;
        }

        return compact('success', 'models');
    }

    private function initializeModel(array $attributes, $scenario)
    {
        $primaryKey = $this->modelClass->getPrimaryKey();
        if (isset($attributes[$primaryKey])) {
            $this->modelClass = $this->modelClass::find($attributes[$primaryKey]);
            $this->modelClass->setScenario(RestModel::UPDATE_SCENARIO);
        } else {
            $this->modelClass = new $this->modelClass;
        }
    }
}
