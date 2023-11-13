<?php
namespace CrudApiRestfull\Contracts;

interface RestModelInterface
{
    public function getLinksAttribute();
    public function getDeletableAttribute();
}