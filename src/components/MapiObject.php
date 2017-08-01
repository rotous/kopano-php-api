<?php

namespace Kopano\Api;

abstract class MapiObject {
	protected $_entryId;
	protected $_resource;
	protected $_properties = array();

	/**
	 * Every message or folder can define his own default property keys
	 * in this array. They will be fetched upon the first call to
	 * getProperties() (or getProperty())
	 */
	protected $_defaultPropertyKeys = array();
	protected $_defaultPropertiesFetched = false;

	public function __construct($entryId=NULL){
		$this->setEntryId($entryId);

		$this->_init();
	}

	/**
	 * Subclasses should implement this function to add their own property keys
	 */
	protected function _init() {}

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

	protected function _addPropertyKeys($propertyKeys){
		$this->_defaultPropertyKeys = array_values(array_unique(array_merge($this->_defaultPropertyKeys, $propertyKeys)));
	}

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
