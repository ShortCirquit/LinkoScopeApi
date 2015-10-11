<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-10-11
 * Time: 10:24 AM
 */

namespace ShortCirquit\LinkoScopeApi\Models;


class BaseModel
{
    public function __construct(Array $cfg = []){
        foreach ($cfg as $k => $v){
            if (property_exists($this, $k)){
                $this->$k = $v;
            }
            else{
                throw new ModelException("Model does not have parameter: $k");
            }
        }
    }
}
