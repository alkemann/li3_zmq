<?php
/**
 * Li3_Zmq - ZeroMQ plugin for Lithium
 *
 * @package       li3_zmq
 * @copyright     Copyright 2011, Redpill-Linpro (http://redpill-linpro.com)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_zmq\extensions\data\source\zeromq;

/**
 * Map results to li3 expections
 */
class Result extends \lithium\core\Object implements \Iterator {

	protected $_iterator = 0;

	protected $_current = null;

	protected $_data = array();

	protected $_resource = null;

	public function __construct(array $config = array()) {
		$defaults = array('resource' => null);
		parent::__construct($config + $defaults);
		$this->_resource = json_decode($this->_config['resource'], true);
		unset($this->_config['resource']);
	}

	public function resource() {
		return $this->_resource;
	}

	public function rewind() {
		return reset($this->_resource);
	}

	public function valid() {
		return !empty($this->_resource) 
				&& isset ($this->_resource['name']) && $this->_resource['name'] == 'Container'
				&& isset ($this->_resource['data']) && !empty($this->_resource['data']);
	}

	public function current() {
		return current($this->_resource);
	}

	public function key() {
		return key($this->_resource);
	}

	public function next() {
		next($this->_resource);
	}

	public function stats() {
		return array('total' => $this->_resource['total']);
	}

	public function data() {
		return is_array($this->_resource['data']) ? $this->_resource['data'] : array();
	}

	public function type() {
		return $this->_resource['type'];
	}

}
