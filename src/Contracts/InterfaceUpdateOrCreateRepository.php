<?php

namespace CrudApiRestfull\Contracts;

interface InterfaceUpdateOrCreateRepository
{
    public function selfValidate(array $attributes, $scenario = 'create', $specific = false);
    public function save(array $attributes, string $scenario = 'create');
    public function create(array $params);
    public function saveArray(array $attributes, $scenario = 'create');
    public function update(string|int  $id, array $attributes);
    public function updateMultiple(array $attributes);
}
