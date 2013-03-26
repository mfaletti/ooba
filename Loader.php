<?php
/**
 * Loader.php
 *
 * @category  Ooba
 * @package   Ooba_Loader
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Loader
{
    /**
     * The default namespaces
     * @var array
     */
    protected $_namespaces = array();
    
    /**
     * Construct
     * 
     * @param array $namespaces (Optional) An array of possible namespaces
     */
    public function __construct(array $namespaces = array())
    {
        if (empty($namespaces) === false) {
            $this->_namespaces = $namespaces;
        }
    }

	/**
     * Set the Default Namespace
     * 
     * @param  array $namespaces An array of possible namespaces
     * @return void
     */
    public function setNamespaces(array $namespaces)
    {
        $this->_namespaces = $namespaces;
    }
    
    /**
     * Get the Default Namespace
     * 
     * @return array
     */
    public function getNamespaces()
    {
        return $this->_namespaces;
    }
    
    /**
     * Factory To dynamically create objects from the proper namespace.
     * arguments beyond the $classname will be passed to the constructor of the created object
     * 
     * @param  string $className: The suffix of the class you are trying to instantiate
     * @return mixed
     */
    public function factory($className)
    {
        $className = $this->getClassName($className);

        // Get a reflection object
        $mirror = new ReflectionClass($className);

        // Test for a constructor
        if ($mirror->getConstructor() === null) {
            return $mirror->newInstance();
        } else {
            $args = func_get_args();
            array_shift($args);
            return $mirror->newInstanceArgs($args);
        }
        
        return false;
    }
    
    /**
     * GetClassName
     * Find the name of the proper class to use.
     * 
     * @param  string $className The suffix of the class you are trying to instantiate
     * @throws Exception You must set a namespace
     * @return mixed
     */
    public function getClassName($className)
    {
        if (empty($this->_namespaces) === true) {
            throw new Ooba_Exception("Unable to load class '$className'. No Namespaces set");
        }

        foreach ($this->_namespaces as $namespace) {
            $class = $namespace . '_' . $className;
            if (class_exists($class) === true) {
                $className = $class;
                break;
            }
        }
        
        return $className;
    }

	/**
     * GetStatic 
     * Dynamically grab the static value of a class
     * 
     * @param  string $className The suffix of the class you are trying to instantiate
     * @param  string $varName   The variable value to retrieve.
     * @return mixed
     */
    public function getStatic($className, $varName)
    {
        return $this->getConstant($className, '$' . $varName);
    }

    /**
     * GetConstant
     *      Dynamically grab the const value of a class
     *
     * @param  string $className The suffix of the class you are trying to instantiate
     * @param  string $varName   The variable value to retrieve.
     * @throws Exception Invalid variable name
     * @return mixed
     */
    public function getConstant($className, $varName)
    {
        $className = $this->getClassName($className);

        if (preg_match('/^[a-zA-Z_0-9$]+$/', $varName) === 0) {
            throw new Ooba_Exception('Invalid variable name supplied to getConstant method');
        }

        if (preg_match('/^[a-zA-Z_0-9]+$/', $className) === 0) {
            throw new Ooba_Exception("Invalid class name supplied to getConstant method");
        }

        return eval('return ' . $className . '::' . $varName . ';');
    }

    /**
     * Call a static method on the class
     *
     * @param  string $className  Classname
     * @param  string $methodName Method name
     * @throws Exception Method does not exist
     * @return mixed
     */
    public function staticMethod($className, $methodName)
    {
        $className = $this->getClassName($className);

        if (method_exists($className, $methodName) === false) {
            throw new Ooba_Exception("Method '$methodName' does not exist for class '$className'");
        }

        $args = array_slice(func_get_args(), 2);
        return call_user_func_array("$className::$methodName", $args);
    }
}
