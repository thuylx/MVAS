<?php

/**
 * represents the security settings of a dwoo instance, it can be passed around to different dwoo instances
 *
 * This software is provided 'as-is', without any express or implied warranty.
 * In no event will the authors be held liable for any damages arising from the use of this software.
 *
 * @author     Jordi Boggiano <j.boggiano@seld.be>
 * @copyright  Copyright (c) 2008, Jordi Boggiano
 * @license    http://dwoo.org/LICENSE   Modified BSD License
 * @link       http://dwoo.org/
 * @version    1.0.0
 * @date       2008-10-23
 * @package    Dwoo
 */
class Dwoo_Security_Policy
{
	/**#@+
	 * php handling constants, defaults to PHP_REMOVE
	 *
	 * PHP_REMOVE : remove all <?php ?> (+ short tags if your short tags option is on) from the input template
	 * PHP_ALLOW : leave them as they are
	 * PHP_ENCODE : run htmlentities over them
	 *
	 * @var int
	 */
	const PHP_ENCODE = 1;
	const PHP_REMOVE = 2;
	const PHP_ALLOW = 3;
	/**#@-*/

	/**#@+
	 * constant handling constants, defaults to CONST_DISALLOW
	 *
	 * CONST_DISALLOW : throw an error if {$dwoo.const.*} is used in the template
	 * CONST_ALLOW : allow {$dwoo.const.*} calls
	 */
	const CONST_DISALLOW = false;
	const CONST_ALLOW = true;
	/**#@-*/

	/**
	 * php functions that are allowed to be used within the template
	 *
	 * @var array
	 */
	protected $allowedPhpFunctions = array
	(
		'str_repeat' => true,
		'number_format' => true,
		'htmlentities' => true,
		'htmlspecialchars' => true,
		'long2ip' => true,
		'strlen' => true,
		'list' => true,
		'empty' => true,
		'count' => true,
		'sizeof' => true,
		'in_array' => true,
		'is_array' => true,
	);

	/**
	 * methods that are allowed to be used within the template
	 *
	 * @var array
	 */
	protected $allowedMethods = array();

	/**
	 * paths that are safe to use with include or other file-access plugins
	 *
	 * @var array
	 */
	protected $allowedDirectories = array();

	/**
	 * stores the php handling level
	 *
	 * defaults to Dwoo_Security_Policy::PHP_REMOVE
	 *
	 * @var int
	 */
	protected $phpHandling = self::PHP_REMOVE;

	/**
	 * stores the constant handling level
	 *
	 * defaults to Dwoo_Security_Policy::CONST_DISALLOW
	 *
	 * @var bool
	 */
	protected $constHandling = self::CONST_DISALLOW;

	/**
	 * adds a php function to the allowed list
	 *
	 * @param mixed $func function name or array of function names
	 */
	public function allowPhpFunction($func)
	{
		if (is_array($func))
			foreach ($func as $fname)
				$this->allowedPhpFunctions[strtolower($fname)] = true;
		else
			$this->allowedPhpFunctions[strtolower($func)] = true;
	}

	/**
	 * removes a php function from the allowed list
	 *
	 * @param mixed $func function name or array of function names
	 */
	public function disallowPhpFunction($func)
	{
		if (is_array($func))
			foreach ($func as $fname)
				unset($this->allowedPhpFunctions[strtolower($fname)]);
		else
			unset($this->allowedPhpFunctions[strtolower($func)]);
	}

	/**
	 * returns the list of php functions allowed to run, note that the function names
	 * are stored in the array keys and not values
	 *
	 * @return array
	 */
	public function getAllowedPhpFunctions()
	{
		return $this->allowedPhpFunctions;
	}

	/**
	 * adds a class method to the allowed list, this must be used for
	 * both static and non static method by providing the class name
	 * and method name to use
	 *
	 * @param mixed $class class name or array of array('class', 'method') couples
	 * @param string $method method name
	 */
	public function allowMethod($class, $method = null)
	{
		if (is_array($class))
			foreach ($class as $elem)
				$this->allowedMethods[strtolower($elem[0])][strtolower($elem[1])] = true;
		else
			$this->allowedMethods[strtolower($class)][strtolower($method)] = true;
	}

	/**
	 * removes a class method from the allowed list
	 *
	 * @param mixed $class class name or array of array('class', 'method') couples
	 * @param string $method method name
	 */
	public function disallowMethod($class, $method = null)
	{
		if (is_array($class))
			foreach ($class as $elem)
				unset($this->allowedMethods[strtolower($elem[0])][strtolower($elem[1])]);
		else
			unset($this->allowedMethods[strtolower($class)][strtolower($method)]);
	}

	/**
	 * returns the list of class methods allowed to run, note that the class names
	 * and method names are stored in the array keys and not values
	 *
	 * @return array
	 */
	public function getAllowedMethods()
	{
		return $this->allowedMethods;
	}

	/**
	 * adds a directory to the safelist for includes and other file-access plugins
	 *
	 * note that all the includePath directories you provide to the Dwoo_Template_File class
	 * are automatically marked as safe
	 *
	 * @param mixed $path a path name or an array of paths
	 */
	public function allowDirectory($path)
	{
		if (is_array($path))
			foreach ($path as $dir)
				$this->allowedDirectories[realpath($dir)] = true;
		else
			$this->allowedDirectories[realpath($path)] = true;
	}

	/**
	 * removes a directory from the safelist
	 *
	 * @param mixed $path a path name or an array of paths
	 */
	public function disallowDirectory($path)
	{
		if (is_array($path))
			foreach ($path as $dir)
				unset($this->allowedDirectories[realpath($dir)]);
		else
			unset($this->allowedDirectories[realpath($path)]);
	}

	/**
	 * returns the list of safe paths, note that the paths are stored in the array
	 * keys and not values
	 *
	 * @return array
	 */
	public function getAllowedDirectories()
	{
		return $this->allowedDirectories;
	}

	/**
	 * sets the php handling level, defaults to REMOVE
	 *
	 * @param int $level one of the Dwoo_Security_Policy::PHP_* constants
	 */
	public function setPhpHandling($level = self::PHP_REMOVE)
	{
		$this->phpHandling = $level;
	}

	/**
	 * returns the php handling level
	 *
	 * @return int the current level, one of the Dwoo_Security_Policy::PHP_* constants
	 */
	public function getPhpHandling()
	{
		return $this->phpHandling;
	}

	/**
	 * sets the constant handling level, defaults to CONST_DISALLOW
	 *
	 * @param bool $level one of the Dwoo_Security_Policy::CONST_* constants
	 */
	public function setConstantHandling($level = self::CONST_DISALLOW)
	{
		$this->constHandling = $level;
	}

	/**
	 * returns the constant handling level
	 *
	 * @return bool the current level, one of the Dwoo_Security_Policy::CONST_* constants
	 */
	public function getConstantHandling()
	{
		return $this->constHandling;
	}

	/**
	 * this is used at run time to check whether method calls are allowed or not
	 *
	 * @param Dwoo_Core $dwoo dwoo instance that calls this
	 * @param object $obj any object on which the method must be called
	 * @param string $method lowercased method name
	 * @param array $args arguments array
	 * @return mixed result of method call or unll + E_USER_NOTICE if not allowed
	 */
	public function callMethod(Dwoo_Core $dwoo, $obj, $method, $args)
	{
		foreach ($this->allowedMethods as $class => $methods) {
			if (!isset($methods[$method])) {
				continue;
			}
			if ($obj instanceof $class) {
				return call_user_func_array(array($obj, $method), $args);
			}
		}
		$dwoo->triggerError('The current security policy prevents you from calling '.get_class($obj).'::'.$method.'()');
		return null;
	}

	/**
	 * this is used at compile time to check whether static method calls are allowed or not
	 *
	 * @param mixed $class lowercased class name or array('class', 'method') couple
	 * @param string $method lowercased method name
	 * @return bool
	 */
	public function isMethodAllowed($class, $method = null) {
		if (is_array($class)) {
			list($class, $method) = $class;
		}
		foreach ($this->allowedMethods as $allowedClass => $methods) {
			if (!isset($methods[$method])) {
				continue;
			}
			if ($class === $allowedClass || is_subclass_of($class, $allowedClass)) {
				return true;
			}
		}
		return false;
	}
}
