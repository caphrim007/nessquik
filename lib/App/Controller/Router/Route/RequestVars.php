<?php

/**
 * Route
 *
 * @package    App_Controller
 * @subpackage Router
 * @copyright  Copyright (c) 2008 Rob Allen (rob@akrabat.com)
 */
class App_Controller_Router_Route_RequestVars implements Zend_Controller_Router_Route_Interface
{
    protected $_current = array();

    /**
     * Instantiates route based on passed Zend_Config structure
     */
    public static function getInstance(Zend_Config $config)
    {
        return new self();
    }

    /**
     * Matches a user submitted path with a previously defined route.
     * Assigns and returns an array of defaults on a successful match.
     *
     * @param string Path used to match against this routing map
     * @return array|false An array of assigned values or a false on a mismatch
     */
    public function match($path)
    {
        $frontController = Zend_Controller_Front::getInstance();
        $request = $frontController->getRequest();
        /* @var $request Zend_Controller_Request_Http */
        
        $baseUrl = $request->getBaseUrl();
        if (strpos($baseUrl, 'index.php') !== false) {
            $url = str_replace('index.php', '', $baseUrl);
            $request->setBaseUrl($url);
        }
        
        $params = $request->getParams();
        
        if (array_key_exists('module', $params)
                || array_key_exists('controller', $params)
                || array_key_exists('action', $params)) {
            
            $module = $request->getParam('module', $frontController->getDefaultModule());
            $controller = $request->getParam('controller', $frontController->getDefaultControllerName());
            $action = $request->getParam('action', $frontController->getDefaultAction());

            $result = array('module' => $module, 
                'controller' => $controller, 
                'action' => $action, 
                );
            $this->_current = $result;
            return $result;
        }
        return false;
    }

    /**
     * Assembles a URL path defined by this route
     *
     * @param array An array of variable and value pairs used as parameters
     * @return string Route path with user submitted parameters
     */
    public function assemble($data = array(), $reset=false, $encode = false)
    {
        $frontController = Zend_Controller_Front::getInstance();
        
        if(!array_key_exists('module', $data) && !$reset 
            && array_key_exists('module', $this->_current)
            && $this->_current['module'] != $frontController->getDefaultModule()) {
            $data = array_merge(array('module'=>$this->_current['module']), $data);
        }
        if(!array_key_exists('controller', $data) && !$reset 
            && array_key_exists('controller', $this->_current) 
            && $this->_current['controller'] != $frontController->getDefaultControllerName()) {
            $data = array_merge(array('controller'=>$this->_current['controller']), $data);
        }
        if(!array_key_exists('action', $data) && !$reset 
            && array_key_exists('action', $this->_current)
            && $this->_current['action'] != $frontController->getDefaultAction()) {
            $data = array_merge(array('action'=>$this->_current['action']), $data);
        }
        
        $url = '';
        if(!empty($data)) {
            $urlParts = array();
            foreach($data as $key=>$value) {
                $urlParts[] = $key . '=' . $value;
            }
            $url = '?' . implode('&', $urlParts);
        }

        return $url;
    }
}
