<?php

include_once 'lib/util/ErrorList.php';

class ValidatorList
{
    var $validators = array();
    var $params = array();
    var $errorList;
    
    function ValidatorList()
    {
        $this->errorList = new ErrorList();
    }
    
    function addValidator($path, $params=null)
    {
        $class = path2class($path);
        include_once $path;
        
        if(isset($this->validators[$class])) {
            return;
        }
        
        $this->validators[$class] = new $class();
        $this->params[$class] = $params;
    }
    
    function execute($value)
    {
        $keys = array_keys($this->validators);
        
        foreach($keys as $key) {
            $result  = $this->validators[$key]->execute($value, $this->params[$key]);
            
            if($result !== true) {
                $this->errorList->addError($result);
            }
        }
    }
    
    function &getErrorList()
    {
        return $this->errorList;
    }
}

?>
