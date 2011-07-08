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
 *
 * Create at least two connections in `app/config/boostrap/connections.php` : 
 * {{{
 *  //One called `hub` for the message broker
 *	Connections::add('hub', array(
 *		'type'		=> 'Zeromq',
 *		'protocol'	=> 'tcp',
 *		'port'		=> '5555',
 *		'host'		=> 'localhost',
 *		'socket'	=> \ZMQ::SOCKET_REQ
 *	));
 *
 *  // And one per Model you wish to serve
 *	Connections::add('users', array(
 *		'type'		=> 'Zeromq',
 *		'protocol'	=> 'tcp',
 *		'port'		=> '5556',
 *		'host'		=> '*',
 *		'socket'	=> \ZMQ::SOCKET_REP,
 *		'model'		=> '\app\models\Users'
 *	));
 *
 * }}}
 *
 * You then can start your broker (for example the provided `hub.php`) and
 * `li3 zmq service users` in any order. They should echo out their connectivity.
 *
 * At this point you can access the service from a third location (or just the same app), using
 * the client command (requires only the `hub` connection) with `li3 zmq client get/users`
 *
 * The different client commands are documented by the Route class
 *
 * @see li3_zmq\extensions\net\socket\Route
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

	/**
	 * Start a service
	 *
	 * @param string $resource users
	 * @param int $port 5559
	 * @param string $host localhost
	 * @param string $connection tcp
	 */
	public function service($resource = null) {
		if ($resource === null) {
			$this->error('ERROR: What service would you like to provide today?', array('nl' => 2, 'style' => 'error'));
			die();
		}

		$hub = $this->__connection('hub');

		$responder = $this->__connection($resource);

		/** candy **/
		$this->out('Registering with hub [',array('nl' => 0, 'style' => 'blue'));
		$this->out($hub->connected_to(),array('nl' => 0, 'style' => 'green'));
		$this->out('] for [', array('nl' => 0, 'style' => 'blue'));
		$this->out($resource, array('nl' => 0, 'style' => 'green'));
		$this->out(']', array('nl' => 2, 'style' => 'blue'));
		/** /candy **/

		$port = $responder->connected_to('port');
		$hub->send("register/$resource/$port");
		$reply = $hub->recv();

		if (json_decode($reply, true) !== true) {
			$this->out('ERROR: ',array('nl' => 0, 'style' => 'red'));
			$this->out('Register with Hub failed!',array('nl' => 2, 'style' => 'blue'));
			die();
		}

		while(true) {
			$this->out('Waiting on [',array('nl' => 0, 'style' => 'blue'));
			$this->out($responder->connected_to(),array('nl' => 0, 'style' => 'green'));
			$this->out(']', array('nl' => 1, 'style' => 'blue'));

			$request = $responder->recv(); // Blocking
			$this->out('Received request: [', array('nl' => 0 ,'style' => 'blue'));
			$this->out($request,  array('nl' => 0 ,'style' => 'green'));
			$this->out(']',  array('nl' => 1 ,'style' => 'blue'));

			$route = Router::parse($request);
			$response = new Response($route, $responder->model());
			$result = json_encode($response->request());

			//  Send reply back to client
			$responder->send($result);
		}
	}

	/**
	 * Make a client request
	 *
	 * @param string $resource Resource to query
	 * @param string $query Primary key
	 */
	public function client($request_string) {

		$hub = $this->__connection('hub');

		/** candy **/
		$this->out('Requesting HUB on [', array('nl' => 0, 'style' => 'blue'));
		$this->out($hub->connected_to(), array('nl' => 0, 'style' => 'green'));
		$this->out('] for [', array('nl' => 0, 'style' => 'blue'));
		$this->out($request_string, array('nl' => 0, 'style' => 'green'));
		$this->out(']', array('nl' => 1, 'style' => 'blue'));

		$hub->send($request_string);
		$reply = $hub->recv();

		echo "\n";
		print_r(json_decode($reply, true));
		echo "\n";
	}

	/**
	 * Get the connection called $resource
	 *
	 * @param string $resource
	 * @return \li3_zmq\extensions\data\source\Zeromq 
	 */
	private function __connection($resource) {
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
