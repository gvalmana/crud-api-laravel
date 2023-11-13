<?php

namespace CrudApiRestfull\Contracts;

interface InterfaceServices
{

    public function selfValidate(array $attributes, $scenario = 'create', $specific = false);
    public function save(array $attributes, string $scenario = 'create');
    public function create(array $params);
    public function saveArray(array $attributes, $scenario = 'create');
    public function update(string|int  $id, array $attributes);
    public function updateMultiple(array $attributes);
    public function destroy(string|int $id);
    public function destroyByIds(array $ids);
    public function restore(string|int $id);
    public function restoreByIds(array $ids);
}
