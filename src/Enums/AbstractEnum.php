<?php

namespace CrudApiRestfull\Enums;

abstract class AbstractEnum
 {
     /**
      * Return all constants
      *
      * @return array
      */
     static function getConstants(): array
     {
         $rc = new \ReflectionClass(get_called_class());

         return $rc->getConstants();
     }

     /**
      * Return last constants
      *
      * @return array
      */
     static function lastConstants(): array
     {
         $parentConstants = static::getParentConstants();
         $allConstants = static::getConstants();

         return array_diff($allConstants, $parentConstants);
     }

     /**
      * Return parent constants
      *
      * @return array
      */
     static function getParentConstants(): array
     {
         $rc = new \ReflectionClass(get_parent_class(static::class));
         return $rc->getConstants();
     }

    /**
     * map constant index to value
     *
     * @param int $index
     * @return mixed|null
     */
    static function mapIndexToValue(int $index)/*: mixed*/
     {
         $allConstants = static::getConstants();
         $i = 1;
         foreach ($allConstants as $constant){
             if($index == $i){
                 return $constant;
             }
             $i++;
         }

         return null;
     }

    /**
     * map constant index to value
     *
     * @param int $index
     * @return mixed|null
     */
    static function mapValueToIndex($value)/*: mixed*/
     {
         $allConstants = static::getConstants();
         $i = 1;
         foreach ($allConstants as $key){
             if($value == $key){
                 return $i;
             }
             $i++;
         }

         return null;
     }

     static function createIndexMap(): array
     {
         $allConstants = static::getConstants();
         $i = 1;
         $map = [];

         foreach ($allConstants as $constant => $value){
            $map[$i++] = $value;
         }

         return $map;
     }
 }

