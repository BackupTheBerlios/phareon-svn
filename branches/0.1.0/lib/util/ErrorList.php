<?php

class ErrorList
{
    var $errors = array();
    
    function addError($error)
    {
        function $this->errors[] = $error;
    }
    
    function export()
    {
        return $this->errors;
    }
    
    function import($errors)
    {
        $this->errors = (array) $errors;
    }
    
    function reset()
    {
        $this->errors = array();
    }
    
    function append($errors)
    {
        $this->errors = array_merge($this->errors, $errors);
    }
}

?>
