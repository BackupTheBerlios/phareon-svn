<?php

class Validator
{
    var $error;
    
    function execute($value, $params=null)
    {
        if($this->validate($value, $params)) {
            return true;
        }
        
        return $this->error;
    }
    
    function validate($value, $params=null)
    {
        return true;
    }
}

?>
