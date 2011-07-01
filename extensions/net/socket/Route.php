<?php
/**
 * Li3_Zmq - ZeroMQ plugin for Lithium
 *
 * @package       li3_zmq
 * @copyright     Copyright 2011, Redpill-Linpro (http://redpill-linpro.com)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_zmq\extensions\net\socket;

/**
 * Represent and analyze the request strings of ZeroMQ messages
 */
class Route extends \lithium\core\Object {

	// string $request_type 'get','post','put','delete'
	protected $_type = 'get';
	// string $location empty or pk, 11,12,'hash123','hash456'
	protected $_location = '';
	// array $query array('admin' => 'true')
	protected $_query = array();
	// string $resource 'users','products'
	protected $_resource = '';
	// array
	protected $_post = null;

	public function compile() {

	}

	/**
	 * Export this object as an array
	 *
	 * @return array
	 */
	public function export() {
		$ret = array($this->_type);
		foreach (array('_resource', '_location', '_query', '_post') as $part) {
			if (!empty($this->$part)) {
				$ret[] = $this->$part;
			}
		}
		return $ret;
	}

	public function __toString() {
		return implode('/', $this->export());
	}

	public function __get($key) {
		if (in_array($key, array('type', 'resource', 'location', 'query', 'post'))) {
			return $this->{'_' . $key};
		}
		return parent::__get($key);
	}

	/**
	 *
	 * @param array $options
	 * @param object $context
	 */
	public function match(array $options = array(), $context = null) {

	}

	/**
	 * Analyze request and set request params from it
	 *
	 * @param type $request
	 */
	public function parse($request) {
		$requestArr = explode('/', $request);
		$request_type = $requestArr[0]; // no isset. required
		$resource = $requestArr[1]; // no isset. required
		$location = isset($requestArr[2]) ? $requestArr[2] : false;
		$post = isset($requestArr[3]) ? $requestArr[3] : false;

		$query = array();
		foreach (array('location','post') as $var) {
			if (strpos($$var, '?') !== false) {
				list($$var, $query_string) = explode('?', $$var);
				$params = explode('&', $query_string);
				foreach ($params as $param) {
					$paramArr = explode('=', $param);
					$query[$paramArr[0]] = $paramArr[1];
				}
			}
		}

		switch ($request_type) {
			case 'put' :
				$post = json_decode($post, true);
				break;
			case 'post' :
				$post =  json_decode($location, true);
				$location = false;
				break;
			case 'get' :
			default :
				break;
		}
		$this->_location = $location;
		$this->_post = $post;
		$this->_type = $request_type;
		$this->_query = $query;
		$this->_resource = $resource;
	}

}
