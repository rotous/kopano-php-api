<?php

namespace Kopano\Api;

require_once('User.php');

/**
 * The Server class is the starting point of the Kopano PHP API. From there a user can be retrieved
 * which has all the functionality;
 */
class Server {
	/**
	 * The address of the Kopano server that is being connected to. Will by default be set to 'default:'
	 * @var String
	 */
	private $_address = 'default:';

	/**
	 * The SSL certificate if the server uses one
	 * @var String
	 */
	private $_sslCertificateFile = NULL;

	/**
	 * The SSL passphrase if needed
	 * @var String
	 */
	private $_sslCertificatePass = NULL;

	/**
	 * The constructor method
	 * @param Array $options The options that will be used to create the Server object. Can contain the
	 * following key/value pairs:
	 * * address The address (url + port) where the server can be reached
	 * * sslCertFile
	 * * sslCertPass
	 */
	public function __construct($options=NULL){
		if ( isset($options) ){
			if ( isset($options['address']) ){
				$this->setAddress($options['address']);
			}
			if ( isset($options['sslCertFile']) ){
				$this->setSslCertificateFile($options['sslCertFile']);
			}
			if ( isset($options['sslCertPass']) ){
				$this->setSslCertificatePass($options['sslCertPass']);
			}
		}
	}

	/**
	 * Sets the address of the server
	 * @param String $address The address of the server
	 */
	public function setAddress($address){
		if ( !isset($address) || !is_string($address) ){
			throw new Exception('No valid server address specified');
		}

		$this->_address = $address;
	}

	/**
	 * Getter for the $_address property
	 * @return String The address of the Kopano server
	 */
	public function getAddress() {
		return $this->_address;
	}

	/**
	 * Setter function for the _sslCertificateFile property
	 * @param String $sslCertificateFile The SSL certificate of the Kopano server
	 */
	public function setSslCertificateFile($sslCertificateFile){
		$this->_sslCertificateFile = $sslCertificateFile;
	}

	/**
	 * Getter function for the _sslCertificateFile property
	 * @return String The SSL certificate of the Kopano server
	 */
	public function getSslCertificateFile() {
		return $this->_sslCertificateFile;
	}

	/**
	 * Setter function for the _sslCertificatePass property
	 * @param String $sslCertificatePass The SSL passphrase of the Kopano server
	 */
	public function setSslCertificatePass($sslCertificatePass){
		$this->_sslCertificatePass = $sslCertificatePass;
	}

	/**
	 * Getter function for the _sslCertificatePass property
	 * @return String The SSL passphrase of the Kopano server
	 */
	public function getSslCertificatePass() {
		return $this->_sslCertificatePass;
	}

	/**
	 * Creates a Kopano\Api\User object that can be used to logon to the server.
	 * @param  String $username The username of the user
	 * @param  String $password The password of the user
	 *
	 * @return Kopano\Api\User A User object for the given user.
	 */
	public function getUser($username, $password){
		$user = new \Kopano\Api\User($username, $password);
		$user->setServer($this);
		return $user;
	}
}
