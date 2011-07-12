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
 *
 * Create or analyze ZeroMQ requests with this class. It sses a string only, RESTful like api:
 *
 * ### Format
 * &lt;type>/&lt;resource/&lt;location>/&lt;post>?&lt;query>
 *
 * ### Examples
 * - get/users
 * - get/posts/12
 * - get/users?admin=1
 * - post/users/{"username":"alkemann","email":"alek@example.org","admin":0}
 * - put/users/5/{"admin":1}
 * - delete/comments/43
 *
 * ### Glosary
 *
 * #### type
 * ##### Type of request
 * Examples: `get`,`post`,`put`,`delete`,`register`,`ping`
 *
 * #### resource
 * ##### Name of resource, lowercase and plural
 * Examples: `/users`,`/posts`,`/comments`
 *
 * #### location
 * ##### Primary key of resource
 * Examples: `/12`, `/asd93khdfjsh3jjsdf2`
 *
 * #### post
 * ##### Post data, payload of PUT and POST, json format
 * Example: `/{"Title":"News","public":1}`
 *
 * #### query
 * ##### Key,value pairs of request conditions
 * Examples: `?admin=0`, `?username=alek&admin=0`
 *
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
	protected $_post = array();
	// mixed
	protected $_send_options = null;

	/**
	 * Export this object as an array
	 *
	 * @return array
	 */
	public function export() {
		$ret = array('type' => $this->_type);
		foreach (array('resource', 'location', 'query', 'post') as $part) {
			$var = '_' . $part;
			if (!empty($this->$var)) {
				$ret[$part] = $this->$var;
			}
		}
		return $ret;
	}

	/**
	 * Output class as string, should be equal to input $request
	 *
	 * @return string
	 */
	public function __toString() {
		$arr = $this->export();
		if (isset($arr['post'])) $arr['post'] = json_encode ($arr['post']);
		$string = '';
		if (isset($arr['query']) && !empty($arr['query'])) {
			$string = '?';
			foreach ($arr['query'] as $k => $v) {
				$string .= "$k=$v&";
			}
			$string = substr($string,0,-1);
			unset($arr['query']);
		}
		$ret = implode('/', array_values($arr));
		$ret .= $string;
		return $ret;
	}

	/**
	 * Get any option to be sent as 2nd param to ZMQSocet::send()
	 *
	 * @return mixed
	 */
	public function sendOptions() {
		return $this->_send_options;
	}

	/**
	 * Get protected properties
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key) {
		if (in_array($key, array('type', 'resource', 'location', 'query', 'post'))) {
			return $this->{'_' . $key};
		}
	}

	public function match(array $options = array(), $context = null) {
	}

	public function compile() {
	}

	/**
	 * Analyze request and set request params from it
	 *
	 * @param type $request
	 * @return Route
	 */
	public function parse($request) {
		$requestArr = explode('/', $request);
		$request = array(
			'request_type' => $requestArr[0], // no isset. required
			'resource' => isset($requestArr[1]) ? $requestArr[1] : null,
			'location' => isset($requestArr[2]) ? $requestArr[2] : false,
			'post' => isset($requestArr[3]) ? $requestArr[3] : false
		);

		$query = array();
		foreach ($request as $var => $value) {
			if (strpos($value, '?') !== false) {
				list($v, $query_string) = explode('?', $value);
				$request[$var] = $v;
				$params = explode('&', $query_string);
				foreach ($params as $param) {
					$paramArr = explode('=', $param);
					$query[$paramArr[0]] = $paramArr[1];
				}
			}
		}

		switch ($request['request_type']) {
			case 'put' :
				$request['post'] = json_decode($request['post'], true);
				break;
			case 'post' :
				$request['post'] =  json_decode($request['location'], true);
				$request['location'] = false;
				break;
			case 'get' :
			default :
				break;
		}
		$this->_location = $request['location'];
		$this->_post = $request['post'];
		$this->_type = $request['request_type'];
		$this->_query = $query;
		$this->_resource = $request['resource'];
		return $this;
	}

	/**
	 * Configure the class based on a Query, as from the ZeroMQ data source
	 *
	 * @param \lithium\data\model\Query $query
	 * @param array $options
	 * @return Route
	 */
	public function generate(\lithium\data\model\Query $query, array $options = array()) {
		$model = $query->model();
		$pk = $model::key();

		$types = array('read'=>'get', 'create'=>'post', 'update'=>'put', 'delete'=>'delete');
		$this->_type = $types[$query->type()];

		$this->_resource = $model::meta('source');

		$conditions = $query->conditions() ?: array();
		if (isset($options['conditions'])) {
			$conditions = \lithium\util\Set::merge($conditions, $options['conditions']);
		}

		if (isset($conditions[$pk])) {
			$this->_location = $conditions[$pk];
			unset($conditions[$pk]);
		}
		$this->_query = $conditions;

		// Extra logic on a per type basis
		switch ($this->_type) {
			case 'put':
			case 'post':
				$this->_post = $query->data();
				break;
		}
		return $this;
	}

}
