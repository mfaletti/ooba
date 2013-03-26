<?php
/**
 * Interface.php
 *
 * @category  Ooba
 * @package   Loader
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
interface Ooba_Loader_Interface
{
	/**
	 * Set the namespaces on the Object Loader
	 * @param $namespaces: An array of available namespaces to load
	 * @return void
	 */
	static public function setNameSpaces(array $namespaces = array());
	
	/**
     * Return available namespaces from the Object Loader
     * @return array
     */
    static public function getNameSpaces();

    /**
     * Get the full class name using namespaces
     * @param  string $class Base class
     * @return string
     */
	static public function getClassName($class);
	
	/**
     * Takes any items passed into the method and hands them to the Object Loader factory method
     * @return mixed
     */
    static public function get();

    /**
     * staticMethod: call a static method on the class
     * @return mixed
     */
    static public function staticMethod();
	
	/**
     * getStatic: get a static property on the class
     * @return mixed
     */
	static public function getStatic();

    /**
     * GetConstant: Get a constant value of a class
     * @return mixed
     */
    static public function getConstant();
}

?>