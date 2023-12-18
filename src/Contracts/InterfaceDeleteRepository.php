<?php

namespace CrudApiRestfull\Contracts;

interface InterfaceDeleteRepository
{
    public function destroy(string|int $id);
    public function destroyByIds(array $ids);
    public function restore(string|int $id);
    public function restoreByIds(array $ids);
}
