<?php

namespace CrudApiRestfull\Services;

use CrudApiRestfull\Contracts\InterfaceUpdateOrCreateServices;
use CrudApiRestfull\Models\RestModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * @property Model $modelClass
 */
abstract class ServicesCreateOrUpdate implements InterfaceUpdateOrCreateServices
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
        $errors = [];
        if (isset($attributes[$this->modelClass->getPrimaryKey()]) && $scenario == 'create') {
            $scenario = "update";
        }
        $this->modelClass->setScenario($scenario);
        $this->modelClass->fill($attributes);
        $validation = $this->modelClass->selfValidate($this->modelClass->getScenario(), $specific, false);
        return $validation;
    }

    public function create(array $params)
    {
        if (isset($params['data']) && is_array($params['data'])) {
            $result = $this->saveArray($params['data']);
        } else {
            $result = $this->save($params);
        }

        return $result;
    }

    public function save(array $attributes, $scenario = 'create')
    {
        $success = true;
        $model = null;
        $errors = null;
        $this->initializeModel($attributes, $scenario);

        $validateResult = $this->selfValidate($attributes, $this->modelClass->getScenario());

        if (!$validateResult['success']) {
            $success = false;
            $errors = $validateResult['errors'];
            $model = $validateResult['model'];
        } else {
            $this->modelClass->fill($attributes);
            $this->modelClass->save();
            $model = $this->modelClass->getAttributes();
        }

        return compact('success', 'errors', 'model');
    }

    public function saveArray(array $attributes, $scenario = 'create')
    {
        $success = true;
        $models = [];

        foreach ($attributes as $data) {
            $result = $this->save($data, $scenario);

            if (!$result['success']) {
                $success = false;
            }

            $models['models'][] = $result['model'];
        }

        return compact('success', 'models');
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
        return compact('success', 'model');
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
        return compact('success', 'models');
    }

    private function initializeModel(array $attributes, $scenario)
    {
        if (isset($attributes[$this->modelClass->getPrimaryKey()])) {
            $this->modelClass = $this->modelClass::find($attributes[$this->modelClass->getPrimaryKey()]);
            $this->modelClass->setScenario('update');
        } else {
            $this->modelClass = new $this->modelClass;
        }
    }
}
