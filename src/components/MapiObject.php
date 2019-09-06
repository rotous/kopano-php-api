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
	 * @var Boolean
	 */
	protected $_defaultPropertiesFetched = false;

	/**
	 * Constructor
	 * @param String $entryId The entryid of the MAPI object that is represented by this instance
	 */
	public function __construct($entryId=NULL){
		$this->setEntryId($entryId);

		$this->_init();
	}

	/**
	 * Initializes the instance of this class by populating the _defaultPropertyKeys property. It will loop
	 * through the class and all its parent up till Kopano\Api\MapiObject and will add all (statically)
	 * defined _propertyKeys arrays.
	 */
	protected function _init() {
		$class = get_class($this);
		while ( $class !== 'Kopano\Api\MapiObject' ){
			if ( isset($class::$_propertyKeys) && is_array($class::$_propertyKeys) ) {
				$this->_defaultPropertyKeys =
					array_unique(array_merge($this->_defaultPropertyKeys, $class::$_propertyKeys));
			};

			$class = get_parent_class($class);
		}
	}

	/**
	 * Setter method for the entryid of the MAPI object.
	 * There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.
	 * @param String $entryId The entryid that should be set on the MAPI object
	 *
	 * @return MapiObject This MapiObject
	 */
	public function setEntryId($entryId) {
		if ( $entryId ){
			$this->_entryId = $entryId;
		}

		return $this;
	}

	/**
	 * Getter method for the _entryId property. Will return false if no entryid is set.
	 * @return String|Boolean The entryid of this MapiObject or false if none was set
	 */
	public function getEntryId() {
		return isset($this->_entryId) ? $this->_entryId : false;
	}

	/**
	 * Setter method for the _resource property.
	 * @param resource $resource The Mapi resource that can be used in calls to the PHP-MAPI extension
	 */
	public function setResource($resource){
		if ( is_resource($resource) ){ // TODO: check the resource type
			$this->_resource = $resource;
		}
	}

	//TODO: I don't like this function...
	/**
	 * Getter method for the _resource property.
	 * @return resource The Mapi resource that can be used in calls to the PHP-MAPI extension
	 */
	public function getResource() {
		return $this->_resource;
	}

	/**
	 * The open function will open the Mapi Object. Subclasses must implement this function and set the _resource
	 * function when the object is opened
	 */
	abstract public function open();

	public function addProperties($properties) {
		foreach ( $properties as $k => $v ){
			$this->_properties[$k] = $v;
		}
	}

	public function getProperties($requestedPropertyKeys=false) {
		$propertyKeys = $requestedPropertyKeys;

		if ( !$this->_defaultPropertiesFetched ){
			if ( !$requestedPropertyKeys ){
				return $this->_properties;
			}

			$propertyKeys = array_merge($requestedPropertyKeys, $this->_defaultPropertyKeys);
			$this->_defaultPropertiesFetched = true;
		}

		if ( $requestedPropertyKeys === false ){
			return $this->_properties;
		}

		$allPropsFetched = true;
		foreach ( $propertyKeys as $propKey ){
			if ( !array_key_exists($propKey, $this->_properties) && !(is_int($propKey) && array_key_exists($propKey | PT_ERROR, $this->_properties)) ){
				$allPropsFetched = false;
				break;
			}
		}

		if ( !$allPropsFetched ){
			// First make sure the item is opened
			$this->open();

			$properties = mapi_getprops($this->_resource, $propertyKeys);
			foreach($properties as $k=>$v) {
				//error_log(Logger::getPropertyString($k) . ' - '. (($k&0xFFFF)) . ' - ' .$k . '-' . PR_ORIGINAL_SUBJECT);
			}

			// Add the named properties with their names as keys
			foreach ($propertyKeys as $k => $v) {
				if (is_string($k)) {
					$properties[$k] = $properties[$v];
				}
			}

			$this->addProperties($properties);
		}

		$properties = array();
		foreach ($requestedPropertyKeys as $key => $keyValue) {
			if (isset($this->_properties[$keyValue])) {
				// Check for named properties
				if (is_string($key)) {
					$properties[$key] = $this->_properties[$key];
				} else {
					$properties[$keyValue] = $this->_properties[$keyValue];
				}
			} elseif (is_int($keyValue)) {
				$keyName = 0xFFFF0000 & $keyValue;
				foreach ($this->_properties as $k => $v) {
					if ($keyName === ($k & 0xFFFF0000)) {
						$properties[$k] = $v;
						break;
					}
				}
				if (key_exists($keyValue | PT_ERROR, $this->_properties)) {
					$properties[$keyValue] = $this->_properties[$keyValue | PT_ERROR];
				}
			}
		}

		return $properties;
	}

	public function getProperty($propertyKey) {
		$properties = $this->getProperties(is_string($propertyKey) ? array($propertyKey => false) : array($propertyKey));
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
