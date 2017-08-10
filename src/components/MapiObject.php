<?php

namespace Kopano\Api;

/**
 * The MapiObject class is the base class for all MAPI objects like stores, folders and messages.
 */
abstract class MapiObject {
	/**
	 * The entryid of the object (binary string)
	 * @var String
	 */
	protected $_entryId;

	/**
	 * The MAPI Resource that is used in the php-mapi functions
	 * @var Resource
	 */
	protected $_resource;

	/**
	 * The properties that have been fetched from the Kopano Server for this MAPI object.
	 * @var Array
	 */
	protected $_properties = array();

	/**
	 * The properties that will be fetched upon the first call to getProperties() or getProperty().
	 * Subclasses can add properties by simply implementing a static protected array called $_propertyKeys.
	 * @example "Item.php" The Item class implements the $_propertyKeys array
	 * @var Array
	 */
	protected $_defaultPropertyKeys = array();

	/**
	 * Initially false. Will be set to true when the properties defined in 
	 * @var [type]
	 */
	protected $_defaultPropertiesFetched = false;

	public function __construct($entryId=NULL){
		$this->setEntryId($entryId);

		$this->_init();
	}

	/**
	 */
	protected function _init() {
		$class = get_class($this);
		while ( $class !== 'Kopano\Api\MapiObject' ){
			if ( isset($class::$_propertyKeys) && is_array($class::$_propertyKeys) ) {
				$this->_defaultPropertyKeys =
					array_values(array_unique(array_merge($this->_defaultPropertyKeys, $class::$_propertyKeys)));
			};

			$class = get_parent_class($class);
		}
	}

	public function setEntryId($entryId) {
		if ( $entryId ){
			$this->_entryId = $entryId;
		}

		return $this;
	}

	public function getEntryId() {
		return isset($this->_entryId) ? $this->_entryId : false;
	}

	public function setResource($resource){
		if ( is_resource($resource) ){ // TODO: check the resource type
			$this->_resource = $resource;
		}
	}

	//TODO: I don't like this function...
	public function getResource() {
		return $this->_resource;
	}

	abstract public function open();

	public function addProperties($properties) {
		foreach ( $properties as $k=>$v ){
			$this->_properties[$k] = $v;
		}
	}

	public function getProperties($propertyKeys=false) {
		if ( !$this->_defaultPropertiesFetched ){
			if ( !$propertyKeys ){
				return $this->_properties;
			}
			$propertyKeys = array_merge($propertyKeys, $this->_defaultPropertyKeys);
			$this->_defaultPropertiesFetched = true;
		}

		if ( $propertyKeys === false ){
			return $this->_properties;
		}

		$allPropsFetched = true;
		foreach ( $propertyKeys as $propKey ){
			if ( !array_key_exists($propKey, $this->_properties) ){
				$allPropsFetched = false;
				break;
			}
		}

		if ( !$allPropsFetched ){
			// First make sure the item is opened
			$this->open();

			$properties = mapi_getprops($this->_resource, $propertyKeys);
			$this->addProperties($properties);
		}

		$properties = array();
		foreach ( $propertyKeys as $key ){
			if ( isset($this->_properties[$key]) ){
				$properties[$key] = $this->_properties[$key];
			}
		}

		return $properties;
	}

	public function getProperty($propertyKey) {
		$properties = $this->getProperties(array($propertyKey));
		return isset($properties[$propertyKey]) ? $properties[$propertyKey] : NULL;
	}

	public function getDefaultPropertyKeys() {
		return $this->_defaultPropertyKeys;
	}

	public function setProperties($properties){
		$this->open();

		try {
			$result = mapi_setprops($this->_resource, $properties);
			if ( $result ){
				$this->addProperties($properties);
			}

			return $result;
		} catch (MAPIException $e) {
			// TODO: Error handling
		}

		return false;
	}

	public function setProperty($key, $value) {
		return $this->setProperties(array($key=>$value));
	}
}
