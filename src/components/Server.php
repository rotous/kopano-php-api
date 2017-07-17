<?php

namespace Kopano\Api;

class Server {
	private $_address = 'default:';
	private $_sslCertificateFile = NULL;
	private $_sslCertificatePass = NULL;

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

	public function setAddress($address){
		$this->_address = $address;
	}

	public function getAddress() {
		return $this->_address;
	}

	public function setSslCertificateFile($sslCertificateFile){
		$this->_sslCertificateFile = $sslCertificateFile;
	}

	public function getSslCertificateFile() {
		return $this->_sslCertificateFile;
	}

	public function setSslCertificatePass($sslCertificatePass){
		$this->_sslCertificatePass = $sslCertificatePass;
	}

	public function getSslCertificatePass() {
		return $this->_sslCertificatePass;
	}
}
