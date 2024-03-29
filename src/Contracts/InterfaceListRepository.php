<?php

namespace CrudApiRestfull\Contracts;

interface InterfaceListRepository
{
    public function show(string|int $id, array $params);
    public function select2List(array $params);
    public function listAll(array $params);
}
