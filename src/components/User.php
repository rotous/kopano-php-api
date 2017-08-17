<?php

namespace Kopano\Api;

require_once(__DIR__ . '/../mapi/mapitags.php');

require_once(__DIR__ . '/Server.php');
require_once(__DIR__ . '/Store.php');

class User {
	/**
	 * The username of the user
	 * @var String
	 * @private
	 */
	private $_username;

	private $_password;

	private $_server;

	private $_mapiSession;
	private $_storeList;

	private $_store;
	private $_publicStore;
	private $_sharedStores;

	public function __construct($username=null, $password=null){
		if ( $username ){
			$this->setUsername($username);
		}

		if ( $password ){
			$this->setPassword($password);
		}
	}

	public function setUsername($username){
		$this->_username = $username;

		return $this;
	}

	public function setPassword($password){
		$this->_password = $password;

		return $this;
	}

	public function setServer($server) {
		if ( $server instanceof Server ){
			$this->_server = $server;
		} else if ( is_array($server) ){
			$this->_server = new Server($server);
		} else {
			$this->_server = new Server();
			if ( is_string($server)  ){
				$this->_server->setAddress($server);

				if ( func_num_args() > 1 ){
					$this->_server->setSslCertificateFile(func_get_arg(1));
					if ( func_num_args() > 2 ){
						$this->_server->setSslCertificatePass(func_get_arg(2));
					}
				}
			}
		}
	}

	/**
	 * Logs the user on to the kopano server
	 */
	public function logon(){
		if ( !isset($this->_server) ){
			throw new Exception('No server set');
		}

		if ( defined('BASE_PATH') && is_file(BASE_PATH.'version') ){
			$webapp_version = 'WebApp-'.trim(file_get_contents(BASE_PATH . 'version'));
		} else {
			$webapp_version = '';
		}
		$browser = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$mapiVersion = phpversion('mapi');

		try{
			if ( version_compare($mapiVersion, '7.2') === 1 ){
				$this->_mapiSession = mapi_logon_zarafa(
					$this->_username,
					$this->_password,
					$this->_server->getAddress(),
					$this->_server->getSslCertificateFile(),
					$this->_server->getSslCertificatePass(),
					1,
					$webapp_version,
					$browser
				);
			} else {
				$this->_mapiSession = mapi_logon_zarafa(
					$this->_username,
					$this->_password,
					$this->_server->getAddress(),
					$this->_server->getSslCertificateFile(),
					$this->_server->getSslCertificatePass(),
					1
				);
			}
		} catch(MAPIException $e){
			// TODO: Error handling
			Logger::log('exception during logon', $e->getMessage());
		}

		return $this;
	}

	public function getStores() {
		if ( !isset($this->_mapiSession) ){
			throw Exception('No mapi session started!');
		}

		try {
			$storesTable = mapi_getmsgstorestable($this->_mapiSession);
			$this->_storelist = mapi_table_queryallrows($storesTable, array(PR_ENTRYID, PR_DEFAULT_STORE, PR_MDB_PROVIDER));
		} catch(MAPIException $e){
			// TODO: Error handling
			Logger:log('exception');
		}

		return $this->_storelist;
	}

	/**
	 * Returns the users own store (opened).
	 *
	 * @return Kopano\Api\Store The users own store
	 */
	public function getStore() {
		if ( !isset($this->_store) ){
			if ( !isset($this->_storeList) ){
				$this->getStores();
			}

			$this->_store = new Store($this->_mapiSession, $this->_storelist[0][PR_ENTRYID]);
			$this->_store->open();
		}

		return $this->_store;
	}

	/**
	 * Opens a store.
	 *
	 * @return Kopano\Api\Store The store that was opened.
	 *
	private function _openStore($entryId){
		if ( !isset($this->_mapiSession) ){
			throw Exception('No mapi session started!');
		}

		try {
			$store = mapi_openmsgstore($this->_mapiSession, $entryId);
		} catch (MAPIException $e){
			//TODO: Error handling
			Logger:log('exception');
		}

		return new Store($store);
	}

	/**
	 * Returns the inbox of the user
	 */
	 public function getInbox() {
		 $store = $this->getStore();
		 $inbox = mapi_msgstore_getreceivefolder($store->getResource());
		 $folder = new Folder(NULL, $store);
		 $folder->setResource($inbox);
  		return $folder;
	 }

	/**
	 * Returns the todo list search folder.
	 *
	 * @return SearchFolder the todo list search folder
	 */
	public function getTodoList() {
 		$store = $this->getStore();
 		return $store->getFolderFromEntryId($store->getTodoListEntryId());
 	}

	public function getCommonViewsFolder() {
		$store = $this->getStore();
 		return $store->getFolderFromEntryId($store->getCommonViewsEntryId(), 'Kopano\Api\CommonViewsFolder');
	}
}
