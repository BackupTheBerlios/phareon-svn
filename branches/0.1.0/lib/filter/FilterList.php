<?php

class FilterList
{
    var $filters = array();
    var $params = array();

    function addFilter($path, $params=null)
    {
        $class = path2class($path);
        include_once $path;

        if(isset($this->filters[$class])) {
            return;
        }

        $this->filters[$class] = new $class();
        $this->params[$class] = $params;
    }

    function execute($value)
    {
        $keys = array_keys($this->filters);

        foreach($keys as $key) {
            $this->filters[$key]->execute($value, $this->params[$key]);
        }
    }
}

?>
