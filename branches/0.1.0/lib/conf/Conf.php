<?php

include_once 'lib/conf/ConfException.php';

/**
 * Conf handles configurations files for different 'domains'
 *
 * Usage example:
 * <code>
 * $conf =& Conf::getInstance();
 * try(); {
 *     $conf->loadConf('conf/database.php', 'database');
 * }
 * if(catch('ConfException', $e)) {
 *    die($e->toString());
 * }
 * $data = $conf->getConf('database');
 * //...
 * </code>
 *
 * @author David Molineus <david at molineus dot de>
 * @since 0.1
 * @package phareon.lib.conf
*/
class Conf
{
    /**
     * loaded domains
     *
     * @since 0.1
     * @access protected
     * @var array
    */
    var $domains = array();
    
    
    /**
     * singleton pattern
     *
     * @since 0.1
     * @access public
     * @return Conf
    */
    function &getInstance()
    {
        static $instance;
        
        if(!isset($instance)) {
            $instance = new Conf();
        }
        
        return $instance;
    }
    
    /**
     * load conf data for a domain
     *
     * @since 0.1
     * @access public
     * @return bool
     * @param string $path path where conf file is stored. include_path is used
     * @param string $domain domain name
     * @throws ConfException Conf.DomainLoadFailed
    */
    function loadConf($path, $domain)
    {
        if(isset($this->domains[$domain])) {
            return true;
        }
        
        $conf = @include($path);
        
        if(!is_array($conf)) {
            throw(new ConfException('Conf.DonainLoadFailed',
                sprintf("Loading conf data from '%s' for domain '%s'. "
                    . "File is invalid, does not exist or is not readable.",
                $path, $domain), __FILE__, __LINE__)
            );
            return false;
        }
        
        $this->domains[$domain] = $conf;
        return true;
    }
    
    /**
     * get conf data
     *
     * Example:
     * domain conf could be:
     * <code>$data = array(
     *     'common' => array(
     *         'title' => 'home',
     *         'url' => '...'
     *     ),
     *     'debug' => true
     * );</code>
     *
     * You can access to the data in different ways:
     * <code>$conf =& Conf::getInstance();
     * $conf->loadConf('conf/site.php', 'site');
     * $conf->getConf('site'); // get hole data
     * $conf->getConf('site', 'common'); // get common array
     * $conf->getConf('site', 'common.title'); //retuns title home
     * </code>
     *
     * @since 0.1
     * @access public
     * @return mixed
     * @param string $domain domain name
     * @param string $path path to access to a part of domain conf data
     * @throws ConfException Conf.DomainNotExist
     * @throws ConfException Conf.ParamNotExist
    */
    function getConf($domain, $path=null)
    {
        if(!isset($this->domains[$domain])) {
            throw(new ConfException('Conf.DomainNotExist',
                sprintf("Could not use domain '%s'. It does not exist.", $domain),
                __FILE__, __LINE__)
            );
            return false;
        }
        
        if($path === null) {
            return $this->domains[$domain];
        }
        
        $parts = explode('.', $path);
        $conf = $this->domains[$domain];
        
        foreach($parts as $part) {
            if(is_array($conf) && isset($conf[$part])) {
                $conf = $conf[$part];
                continue;
            }
            
            throw(new ConfException('Conf.ParamNotExist',
                sprintf("Part '%s' of conf path '%s' does not exists in domain '%s'.",
                    $part, $path, $domain), 
				__FILE__, __LINE)
            );
            return false;
        }
        
        return $conf;
    }
}

?>
