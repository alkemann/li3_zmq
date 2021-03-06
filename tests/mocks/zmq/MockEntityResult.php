<?php
/**
 * Li3_Zmq - ZeroMQ plugin for Lithium
 *
 * @package       li3_zmq
 * @copyright     Copyright 2011, Redpill-Linpro (http://redpill-linpro.com)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_zmq\tests\mocks\zmq;

/**
 * Inspect resource without conversion
 */
class MockEntityResult extends \li3_zmq\extensions\data\source\zeromq\Result {

	protected $_request = '';

	public function __construct(array $config = array()) {
		$defaults = array('resource' => null);
		parent::__construct($config + $defaults);
		$this->_request = $config['resource'];
		$this->_resource = array(
			'type' => 'Entity',
			'data' => array('request' => $config['resource'])
		);
	}

	public function valid() {
		return true;
	}

}
