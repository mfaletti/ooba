<?php
/**
 * Log.php
 *
 * @category  Ooba
 * @package   Ooba_Log
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Log
{
    /**
     * Zend_Log object
     *
     * @var Zend_Log
     */
    protected $_log;
    
    /**
     * Default Zend_Log object
     *
     * @var Zend_Log
     */
    protected $_defaultLog;
    
    /**
     * Singleton instance
     *
     * @var Ooba_Log
     */
    private static $_instance;
    
    /**
     * Construct
     *
     * @param Zend_Log $defaultLog (Optional) The Zend_Log object to use as the default
     */
    private function __construct(Zend_Log $defaultLog = null)
    {
        if ($defaultLog !== null) {
            $this->setDefaultLog($defaultLog);
        }
    }
    
    /**
     * Get Singleton Instance
     *
     * @param  Zend_Log $defaultLog (Optional) The Zend_Log object to use as the default
     * @return Ooba_Log
     */
    public static function getInstance(Zend_Log $defaultLog = null)
    {
        if (self::$_instance === null) {
            self::$_instance = new self($defaultLog);
        } else if ($defaultLog !== null) {
            self::$_instance->setDefaultLog($defaultLog);
        }
        
        return self::$_instance;
    }
    
    /**
     * Set the default Zend_Log object
     *
     * @param  Zend_Log $defaultLog The Zend_Log object to use as default
     * @return void
     */
    public function setDefaultLog(Zend_Log $defaultLog)
    {
        $this->_defaultLog = $defaultLog;
    }
    
    /**
     * Set the Zend_Log object
     *
     * @param  Zend_Log $log The Zend_Log object
     * @return Ooba_Log
     */
    public function setLog(Zend_Log $log)
    {
        $this->_log = $log;
        return $this;
    }
    
    /**
     * Return the Zend_Log object
     *
     * @return Zend_Log
     */
    public function getLog()
    {
        if ($this->_log === null) {
            return $this->_defaultLog;
        }
        
        return $this->_log;
    }
    
    /**
     * Proxy method calls to Zend_Log
     *
     * @param  string $method    The method to be proxied
     * @param  array  $arguments Arguments to be passed to the proxied method
     * @return void
     */
    public function __call($method, array $arguments)
    {
        $log = $this->getLog();
        
        // Just return if the log object isn't set.
        if ($log === null) {
            return;
        }
        
        return call_user_func_array(array($log, $method), $arguments);
    }
    
    /**
     * Reset the instance back to null
     *
     * @return void
     */
    public function resetInstance()
    {
        $this->_defaultLog = null;
        $this->_log        = null;
        self::$_instance   = null;
    }
    
    /**
     * Add stacktrace to debugging
     * 
     * @param  string  $message    Message
     * @param  integer $startTrace (Optional) Depth of stack trace to start output
     * @param  integer $endTrace   (Optional) Depth of stack trace to start output
     * @return void
     */
    public function debug($message, $startTrace = null, $endTrace = null)
    {
        $log = $this->getLog();
        
        // Just return if the log object isn't set.
        if ($log === null) {
            return;
        }
        
        if (is_null($startTrace) === false and is_null($endTrace) === true) {
            $endTrace = $startTrace;
        } else if (is_null($startTrace) === true and is_null($endTrace) === false) {
            $startTrace = $endTrace;
        } else if (is_null($startTrace) === true and is_null($endTrace) === true) {
            $startTrace = 1;
            $endTrace   = 0;
        }
        
        $debug = debug_backtrace();
        $max   = (count($debug) <= $endTrace) ? (count($debug) - 1) : $endTrace;
        for ($i = $startTrace; $i <= $max; $i++) {
            if (array_key_exists('file', $debug[$i]) === true) {
                $message .= "\n\t" . $debug[$i]['file'] . ':' . $debug[$i]['line'];
            }
        }
        
        return $log->debug($message);
    }
}
?>