<?php
/**
 * Li3_Zmq - ZeroMQ plugin for Lithium
 *
 * @package       li3_zmq
 * @copyright     Copyright 2011, Redpill-Linpro (http://redpill-linpro.com)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_zmq\extensions\command;

use li3_zmq\extensions\net\socket\Router;
use li3_zmq\extensions\net\socket\Response;
use lithium\data\Connections;

/**
 * Start or test ZeroMQ sockets and services
 */
class Zmq extends \lithium\console\Command {

	private $__context;

	protected function _init() {
		parent::_init();
		if (!class_exists('ZMQContext')) {
			$this->error('ERROR: ZeroMQ binding for PHP not installed.', array('nl' => 2, 'style' => 'error'));
			die();
		}
		$this->__context = new \ZMQContext();
	}

	public function tes() {
		//$con = Connections::get('hub')->connect();
		print_r($con);
	}

	/**
	 * Start a service
	 *
	 * @param string $resource users
	 * @param int $port 5559
	 * @param string $host localhost
	 * @param string $connection tcp
	 */
	public function service($resource = 'users') {

		$hub = $this->__hub();

		$responder = $this->__responder($resource);

		/** candy **/
		$this->out('Registering with hub [',array('nl' => 0, 'style' => 'blue'));
		$this->out($hub->connected_to(),array('nl' => 0, 'style' => 'green'));
		$this->out('] for [', array('nl' => 0, 'style' => 'blue'));
		$this->out($resource, array('nl' => 0, 'style' => 'green'));
		$this->out(']', array('nl' => 2, 'style' => 'blue'));
		/** /candy **/

		$port = $responder->connected_to('port');
		$reply = $hub->send("register/$resource/$port");

		if (json_decode($reply, true) !== true) {
			$this->out('ERROR: ',array('nl' => 0, 'style' => 'red'));
			$this->out('Register with Hub failed!',array('nl' => 2, 'style' => 'blue'));
			die();
		}

		$this->out('Waiting on [',array('nl' => 0, 'style' => 'blue'));
		$this->out($responder->connected_to(),array('nl' => 0, 'style' => 'green'));
		$this->out(']', array('nl' => 2, 'style' => 'blue'));

		while(true) {
			$request = $responder->recv(); // Blocking
			$this->out('Received request: [', array('nl' => 0 ,'style' => 'blue'));
			$this->out($request,  array('nl' => 0 ,'style' => 'green'));
			$this->out(']',  array('nl' => 1 ,'style' => 'blue'));

			$route = Router::process($request);
			$response = new Response($route);
			$result = $response->request();

			//  Send reply back to client
			$responder->send(json_encode($result));
		}
	}

	/**
	 * Make a client request
	 *
	 * @param string $resource Resource to query
	 * @param string $query Primary key
	 */
	public function client($request_string) {

		$hub = $this->__hub();

		/** candy **/
		$this->out('Requesting HUB on [', array('nl' => 0, 'style' => 'blue'));
		$this->out($hub->connected_to(), array('nl' => 0, 'style' => 'green'));
		$this->out('] for [', array('nl' => 0, 'style' => 'blue'));
		$this->out($request_string, array('nl' => 0, 'style' => 'green'));
		$this->out(']', array('nl' => 1, 'style' => 'blue'));

		$reply = $hub->send($request_string);

		echo "\n";
		print_r(json_decode($reply, true));
		echo "\n";
	}

	private function __hub() {
		$hub = Connections::get('hub');

		/** candy **/
		if ($hub === null) {
			$this->out('ERROR: ',array('nl' => 0, 'style' => 'red'));
			$this->out('Create a connection called "',array('nl' => 0, 'style' => 'blue'));
			$this->out('hub',array('nl' => 0, 'style' => 'green'));
			$this->out('" in ',array('nl' => 0, 'style' => 'blue'));
			$this->out('/app/config/bootstrap/connections.php',array('nl' => 2, 'style' => 'green'));
			die();
		}

		/** /candy **/
		return $hub;
	}

	private function __responder($resource) {
		$responder = Connections::get($resource);

		/** candy **/
		if ($responder === null) {
			$this->out('ERROR: ',array('nl' => 0, 'style' => 'red'));
			$this->out('Create a connection called "',array('nl' => 0, 'style' => 'blue'));
			$this->out($resource,array('nl' => 0, 'style' => 'green'));
			$this->out('" in ',array('nl' => 0, 'style' => 'blue'));
			$this->out('/app/config/bootstrap/connections.php',array('nl' => 2, 'style' => 'green'));
			die();
		}
		/** /candy **/
		return $responder;
	}
}
