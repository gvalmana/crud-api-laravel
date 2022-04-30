<?php
namespace CrudApiRestfull\Models;

interface RestModelInterface
{
    public function getLinksAttribute();
    public function getDeletableAttribute();
}