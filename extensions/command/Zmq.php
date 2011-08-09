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
use li3_zmq\extensions\data\source\zeromq\Envelope;
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
 *		'socket'	=> \ZMQ::SOCKET_QREQ,
 *		'options'	  => array(
 *			\ZMQ::SOCKOPT_LINGER => 0
 *		),
 *	));
 *
 *	// Connections used for service :
 *	Connections::add('service', array(
 *		'type'		=> 'Zeromq',
 *		'protocol'	=> 'tcp',
 *		'port'		=> '5555',
 *		'host'		=> 'localhost',
 *		'socket'	=> \ZMQ::SOCKET_XREQ,
 *		'options'	  => array(
 *			\ZMQ::SOCKOPT_LINGER => 0
 *		),
 *		'model'		=> array(
 *			'items' => '\app\models\Items',
 *			'users'	=> '\users_plugin\models\Users',
 *			'products'	=> '\app\models\Products',
 *			'userproducts'	=> '\users_plugin\models\Products'
 *		)
));
 * }}}
 *
 * You then can start your broker (for example the provided `hub.php`) and
 * `li3 zmq service users` in any order. They should echo out their connectivity.
 *
 * At this point you can access the service from a third location (or just the same app), using
 * the client command (requires only the `hub` connection) with `li3 zmq client get/users`
 *
 * Important note: When using a query with multiple parameters, you must use quote the request
 * such `li3 zmq client "get/users?username=defaultuser&password=abd008dda1e0b78125d11f0ed054710f"`.
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
		$this->__context = new \ZMQContext(1);
	}

	/**
	 * Start a service
	 *
	 * @param string $resources users
	 */
	public function service($resources = null) {
		$verbose = !(isset($this->silent) || isset($this->s));
		if ($resources === null) {
			if ($verbose) {
				$this->error('ERROR: What service would you like to provide today?', array('nl' => 2, 'style' => 'error'));
				exit;
			} else {
				throw new \Exception('No service provided');
			}
		}

		$responder = $this->__connection('service');

		/** candy **/
		if ($verbose) {
			$this->out('Registering as with hub [',array('nl' => 0, 'style' => 'blue'));
			$this->out($responder->connected_to(),array('nl' => 0, 'style' => 'green'));
			$this->out('] for [', array('nl' => 0, 'style' => 'blue'));
			$this->out($resources, array('nl' => 0, 'style' => 'green'));
			$this->out(']', array('nl' => 2, 'style' => 'blue'));
		}
		/** /candy **/

		$responder->send("register/$resources");

		$lastHeardFromHub = microtime(true);
		$read = $write = array();
		$attempts = 0;

		/** candy **/
		if ($verbose) {
			$this->out('Waiting on [',array('nl' => 0, 'style' => 'blue'));
			$this->out($responder->connected_to(),array('nl' => 0, 'style' => 'green'));
			$this->out(']', array('nl' => 1, 'style' => 'blue'));
		}
		/** /candy **/
		while(true) {

			$poll = new \ZMQPoll();
			$poll->add($responder->socket(), \ZMQ::POLL_IN); // use connections config for pull duration
			$events = $poll->poll($read, $write, 3 /** sec ***/ * 1000000);

			$now = microtime(true);

			if ($events) {
				$envelope = new Envelope();
				$envelope->recv($responder->socket());
				$request = $envelope->content;

				$lastHeardFromHub = $now;

				if ($request === 'registered') {
					if ($verbose) {
						echo PHP_EOL;
						$this->out('Registration successful', array('nl' => 0 ,'style' => 'blue'));
						echo PHP_EOL;
					}
					$attempts = 0;
					continue;
				} elseif ($request === 'ping') {
					$envelope->content = 'ping/'.time();
					// Ping back
					$envelope->send($responder->socket());
					if ($verbose) echo '!';
					continue;
				}

				/** candy **/
				if ($verbose) {
					echo PHP_EOL;
					$this->out('Received request: [', array('nl' => 0 ,'style' => 'blue'));
					$this->out($request,  array('nl' => 0 ,'style' => 'green'));
					$this->out(']',  array('nl' => 1 ,'style' => 'blue'));
					echo PHP_EOL;
				}
				/** /candy **/

				$route = Router::parse($request);
				$resource = $route->resource;
				$response = new Response($route, $responder->model($resource));
				$envelope->content = 'reply/'.json_encode($response->request());

				//  Send reply back to client
				$envelope->send($responder->socket());
			} else {
				if ($verbose) echo '.';
			}
			$expiry = $lastHeardFromHub + 9 /** sec **/;
			if ($now > $expiry) {
				if ($attempts++ >= 3) {
					if ($verbose) {
						echo PHP_EOL;
						$this->out('Third attempt to reregister failed! Hub is dead! EXITING!', array('nl' => 0 ,'style' => 'red'));
					}
					exit;
				}
				$lastHeardFromHub = $now;
				if ($verbose) {
					echo PHP_EOL;
					$this->out('Reregistering with hub', array('nl' => 0 ,'style' => 'green'));
				}
				$responder->send("register/$resources");
			}
		}
	}

	/**
	 * Make a client request
	 *
	 * @param string $resource Resource to query
	 * @param string $query Primary key
	 */
	public function client($request_string) {
		$verbose = !(isset($this->silent) || isset($this->s));

		$hub = $this->__connection('hub');

		/** candy **/
		if ($verbose) {
			$this->out('Requesting HUB on [', array('nl' => 0, 'style' => 'blue'));
			$this->out($hub->connected_to(), array('nl' => 0, 'style' => 'green'));
			$this->out('] for [', array('nl' => 0, 'style' => 'blue'));
			$this->out($request_string, array('nl' => 0, 'style' => 'green'));
			$this->out(']', array('nl' => 1, 'style' => 'blue'));
		}
		/** /candy **/

		$hub->send($request_string);
		$reply = $hub->recv();

		echo "\n";
		print_r(json_decode($reply, true));
		echo "\n";
	}

	/**
	 * Get the connection called $resource
	 *
	 * @param string $connection_config
	 * @param string $port
	 * @return \li3_zmq\extensions\data\source\Zeromq
	 */
	private function __connection($connection_config, $port = null) {
		$responder = Connections::get($connection_config);

		/** candy **/
		if ($responder === null) {
			if ($verbose) {
				$this->out('ERROR: ',array('nl' => 0, 'style' => 'red'));
				$this->out('Create a connection called "',array('nl' => 0, 'style' => 'blue'));
				$this->out($resource,array('nl' => 0, 'style' => 'green'));
				$this->out('" in ',array('nl' => 0, 'style' => 'blue'));
				$this->out('/app/config/bootstrap/connections.php',array('nl' => 2, 'style' => 'green'));
				exit;
			} else {
				throw new \Exception('Missing connection');
			}
		}
		/** /candy **/

		if ($port !== null) {
			$responder->change_port($port);
		}

		$responder->connect();

		return $responder;
	}

}
