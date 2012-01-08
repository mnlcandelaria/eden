<?php //-->
/*
 * This file is part of the Eden package.
 * (c) 2009-2011 Christian Blanquera <cblanquera@gmail.com>
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

require_once dirname(__FILE__).'/error.php';

/**
 * The base class for all classes wishing to integrate with Eve.
 * Extending this class will allow your methods to seemlessly be 
 * overloaded and overrided as well as provide some basic class
 * loading patterns.
 *
 * @package    Eden
 * @category   framework	
 * @author     Christian Blanquera <cblanquera@gmail.com>
 * @version    $Id: class.php 1 2010-01-02 23:06:36Z blanquera $
 */
class Eden_Class {
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_event 		= NULL;
	protected $_observers 	= array();
	
	/* Private Properties
	-------------------------------*/
	private static $_instances 	= array();
	
	/* Get
	-------------------------------*/
	/* Magic
	-------------------------------*/
	public function __toString() {
		return get_class($this);
	}
	
	public function __call($name, $args) {
		//if the method name starts with a capital letter
		//most likely they want a class
		if(preg_match("/^[A-Z]/", $name)) {
			//lets first consider that they may just
			//want to load a class so lets try
			try {
				//return the class
				return Eden_Route::i()->getClass($name, $args);
			//only if there's a route exception do we want to catch it
			//this is because a class can throw an exception in their construct
			//so if that happens then we do know that the class has actually
			//been called and an exception is suppose to happen
			} catch(Eden_Route_Error $e) {}
		}
		
		try {
			//let the router handle this
			return Eden_Route::i()->getMethod()->call($this, $name, $args);
		} catch(Eden_Route_Error $e) {
			Eden_Error::i($e->getMessage())->trigger();
		}
	}
	
	/* Public Methods
	-------------------------------*/
	/**
	 * Creates a class route for this class.
	 * 
	 * @param *string the class route name
	 * @return Eden_Class
	 */
	public function routeThis($route) {
		//argument 1 must be a string
		Eden_Error::i()->argument(1, 'string');
		
		if(func_num_args() == 1) {
			//when someone calls a class call this instead
			Eden_Route::i()->getClass()->route($route, $this);
			return $this;
		}
		
		//argument 2 must be a string
		Eden_Error::i()->argument(2, 'string', 'object');
		
		$args = func_get_args();
		
		$source = array_shift($args);
		$class 	= array_shift($args);
		$destination = NULL;
		
		if(count($args)) {
			$destination = array_shift($args);
		}
		
		//when someone calls a method here call something ele instead
		Eden_Route::i()->getMethod()->route($this, $source, $class, $destination);
		return $this;
	}
	
	/**
	 * Calls a method in this class and allows 
	 * argumetns to be passed as an array
	 *
	 * @param string
	 * @param array
	 * @return mixed
	 */
	public function callThis($method, array $args = array()) {
		//argument 1 must be a string
		Eden_Error::i()->argument(1,'string');
		
		return Eden_Route::i()->getMethod($this, $method, $args);
	}
	
	/**
	 * Invokes When if conditional is false
	 *
	 * @param bool
	 * @return this|Eden_Noop
	 */
	public function when($isTrue, $lines) {
		if($isTrue) {
			return $this;
		}
		
		return Eden_When::i($this, $lines);
	}
	
	/**
	 * Invokes Chain map
	 *
	 * @param bool
	 * @return this|Eden_Noop
	 */
	public function loop(array &$list, $lines = 0) {
		return Eden_Map::i($this, $list, $lines);
	}
	
	/* Protected Methods
	-------------------------------*/
	protected static function _getSingleton($class) {
		$class = Eden_Route::i()->getClass()->getRoute($class);
		
		if(!isset(self::$_instances[$class])) {
			$args = func_get_args();
			array_shift($args);
			
			self::$_instances[$class] = self::_getInstance($class, $args);
		}
		
		return self::$_instances[$class];
	}
	
	protected static function _getMultiple($class) {
		$args = func_get_args();
		$class = array_shift($args);
		
		$class = Eden_Route::i()->getClass()->getRoute($class);
		
		return self::_getInstance($class, $args);
	}
	
	/* Private Methods
	-------------------------------*/
	private static function _getInstance($class, $args) {
		if(count($args) === 0 || !method_exists($class, '__construct')) {
			return new $class;
		}
		
		$reflect = new ReflectionClass($class);
		
		try {
			return $reflect->newInstanceArgs($args);
		} catch(Reflection_Exception $e) {
			Eden_Error::i()
				->setMessage(Eden_Error::REFLECTION_ERROR) 
				->addVariable($class)
				->addVariable('new')
				->trigger();
		}
	}
}

require_once dirname(__FILE__).'/route.php';
require_once dirname(__FILE__).'/when.php';
require_once dirname(__FILE__).'/map.php';