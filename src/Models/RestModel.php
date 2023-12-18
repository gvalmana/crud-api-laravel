<?php

namespace CrudApiRestfull\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Validator;

class RestModel extends Model
{
    /**
     * @param array $parameters
     * @return mixed
     */
    public const CREATE_SCENARIO = 'create';
    public const UPDATE_SCENARIO = 'update';
    protected $scenario = self::CREATE_SCENARIO;
    /**
     * /**
     * The name of the model name parameters
     *
     * @var string
     */
    const MODEL = '';

    /**
     * /**
     * Relations of entity
     *
     * @var array
     */
    const RELATIONS = [];

    const PARENT = [];

    /**
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }


    /**
     * @return mixed
     */
    public function getScenario()
    {
        return $this->scenario;
    }

    /**
     * @param mixed $scenario
     */
    public function setScenario($scenario): void
    {
        $this->scenario = $scenario;
    }

    public function hasHierarchy()
    {
        return count(get_called_class()::PARENT) > 0;
    }

    protected function rules($scenario)
    {
        return [];
    }

    /**
     * @param array $parameters
     * @return mixed
     */

    public function selfValidate($scenario = 'create', $specific = false, $validate_pk = true)
    {
        $rules = $this->rules[$scenario];
        if (!$validate_pk) {
            unset($rules[$this->getPrimaryKey()]);
        }
        if ($specific) {
            $rules = array_intersect_key($rules, $this->attributes);
        } else {
            $rules = $this->rules[$scenario];
        }
        $validator = Validator::make($this->attributes, $rules);
        if ($validator->fails()) {
            $success = false;
            $errors = $validator->errors();
            $model = get_called_class();
        } else {
            $success = true;
            $errors = null;
            $model = $this;
        }
        $response = compact('success', 'errors', 'model');
        return $response;
    }

    public function validateAll(array $attributes, $scenario = 'create', $specific = false)
    {
        $validate = [];
        if (isset($attributes[$this->getPrimaryKey()]) && $scenario == 'create')
            $scenario = "update";
        $this->setScenario($scenario);
        if (count(self::PARENT) > 0) {
            $parent_class = self::PARENT['class'];
            if (!isset($attributes[$this->getPrimaryKey()])) {
                $parent = new $parent_class();
            } else {
                $parent = $parent_class::find($attributes[$this->getPrimaryKey()]);
            }
            if (!$parent) {
                $result = ["success" => false, 'error' => "Element not found", "model" => $parent_class];
                return $result;
            }
            $validateparents = $this->parents_validate($attributes, $this->getScenario(), $specific);
            if ($validateparents)
                $validate[] = $validateparents;
        }
        $this->fill($attributes);
        $valid = $this->self_validate($this->getScenario(), $specific, false);
        if ($valid['success'] && count($validate) == 0) {
            $result = ["success" => true, 'error' => []];
        } else {
            if (!$valid['success'])
                array_push($validate, $valid);
            $result = ["success" => false, "errors" => $validate];
        }
        return $result;
    }

    public function getPrincipalAttribute()
    {
        return null;
    }
}
