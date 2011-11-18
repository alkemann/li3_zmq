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
    protected $beat_interval = 3000; // msec

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
	 *  Usage: li3 zmq service resource[,resource] [--log|beat|data]
	 *	--log	Provide text output
	 *	--beat	Include beats in output
	 *	--data	Include data in output
	 *
	 * Requires a connection called 'service' in `config\bootstrap\connections.php`
	 *
	 * @param string $resources users
	 */
	public function service($resources = null) {
		$log = isset($this->log);
		$beat = isset($this->beat);
		$data = isset($this->data);

		if ($resources === null) {
			if ($log) {
				$this->error('ERROR: What service would you like to provide today?', array('nl' => 2, 'style' => 'error'));
				exit;
			} else {
				throw new \Exception('No service provided');
			}
		}

		$responder = $this->__connection('service');
		$responder->identity(isset($this->identity) ? $this->identity : gethostname().'.'.$resources);
		$responder->connect();

		/** candy **/
		if ($log) {
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
		if ($log) {
			$this->out('Waiting on [',array('nl' => 0, 'style' => 'blue'));
			$this->out($responder->connected_to(),array('nl' => 0, 'style' => 'green'));
			$this->out(']', array('nl' => 1, 'style' => 'blue'));
		}
		/** /candy **/
		while(true) {

			$poll = new \ZMQPoll();
			$poll->add($responder->socket(), \ZMQ::POLL_IN); // use connections config for pull duration
			$events = $poll->poll($read, $write, $this->milliseconds($this->beat_interval));

			$now = microtime(true);

			if ($events) {
				$envelope = new Envelope();
				$envelope->recv($responder->socket());
				$request = $envelope->content;

				$lastHeardFromHub = $now;

				if ($request === 'registered') {
					if ($log) {
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
					if ($beat) echo '!';
					continue;
				}

				/** candy **/
				if ($log) {
					if ($beat) echo PHP_EOL;
					$this->out('Received request: [', array('nl' => 0 ,'style' => 'blue'));
					$this->out($request,  array('nl' => 0 ,'style' => 'green'));
					$this->out(']',  array('nl' => 1 ,'style' => 'blue'));
				}
				/** /candy **/

				$route = Router::parse($request);
				$resource = $route->resource;
				$response = new Response($route, $responder->model($resource));
				$envelope->content = 'reply/'.json_encode($response->request());

				if ($data) {
					echo PHP_EOL;
					$this->out('Sending reply: [', array('nl' => 0 ,'style' => 'blue'));
					$this->out($envelope->content,  array('nl' => 0 ,'style' => 'green'));
					$this->out(']',  array('nl' => 1 ,'style' => 'blue'));
					echo PHP_EOL;
				}

				//  Send reply back to client
				$envelope->send($responder->socket());
			} else {
				if ($beat) echo '.';
			}
			$expiry = $lastHeardFromHub + 9 /** sec **/;
			if ($now > $expiry) {
				if ($attempts++ >= 3) {
					if ($log) {
						echo PHP_EOL;
						$this->out('Third attempt to reregister failed! Hub is dead! EXITING!', array('nl' => 0 ,'style' => 'red'));
					}
					exit;
				}
				$lastHeardFromHub = $now;
				if ($log) {
					echo PHP_EOL;
					$this->out('Reregistering with hub', array('nl' => 0 ,'style' => 'green'));
				}
				$responder->send("register/$resources");
			}
		}
	}

	/**
	 * Send status messages to HUB, check if it is alive
	 *  Usage: li3 zmq supervise [delay=60.0] [--log|beat|data]
	 *	--log	Provide text output
	 *	--beat	Include beats in output
	 *
	 * @param float $delay Time between requests in seconds
	 */
	public function supervise($delay = 60.0) {
		$log = isset($this->log);
		$beat = isset($this->beat);

		$hub = $this->__connection('hub')->connect();

		$timeout = $this->secondsToMilliSeconds($delay);
		$attempts = 0;

		$read = $write = array();
		$hub->send('status');
		while (true) {
			$poll = new \ZMQPoll();
			$poll->add($hub->socket(), \ZMQ::POLL_IN); // use connections config for pull duration
			$events = $poll->poll($read, $write, $this->milliseconds($timeout));
			if ($events) {
				$attempts = 0;
				$status = $hub->recv();
				if ($beat) $this->out('!', array('nl' => 1));
				if ($log) $this->out('Alive: ', array('nl' => 0, 'style' => 'blue'));
				if ($log) $this->out($status, array('nl' => 1, 'style' => 'green'));
			} else {
				if (++$attempts >= 3) {
					if ($beat) $this->out('', array('nl' => 1));
					$this->out('!ERROR! ', array('nl' => 0, 'style' => 'red'));
					$this->out('HUB has not responded for [', array('nl' => 0, 'style' => 'green'));
					$this->out($delay * $attempts, array('nl' => 0, 'style' => 'blue'));
					$this->out('] seconds!', array('nl' => 1, 'style' => 'green'));
				}
				if ($beat) $this->out('?', array('nl' => 0));
				$hub->send('status');
			}
		}
	}

	/**
	 * Make a client request
	 *  Usage: li3 zmq client [request string] [--log]
	 *	--log	Provide text output
	 *
	 * Requires a connection called 'hub' in `config\bootstrap\connections.php`
	 *
	 * @param string $resource Resource to query
	 * @param string $query Primary key
	 */
	public function client($request_string) {
		$log = isset($this->log);

		$hub = $this->__connection('hub')->connect();

		/** candy **/
		if ($log) {
			$this->out('Requesting HUB on [', array('nl' => 0, 'style' => 'blue'));
			$this->out($hub->connected_to(), array('nl' => 0, 'style' => 'green'));
			$this->out('] for [', array('nl' => 0, 'style' => 'blue'));
			$this->out($request_string, array('nl' => 0, 'style' => 'green'));
			$this->out(']', array('nl' => 1, 'style' => 'blue'));
		}
		/** /candy **/

		$hub->send($request_string);

		$result = array();
		do {
			$reply = $hub->socket()->recvMulti();
			$msg = array_pop($reply);
			list($type, $content) = explode('/', $msg, 2);
			switch ($type) {
				case 'error': die('ERROR: ' . $content. PHP_EOL); break;
				case 'part' :
					echo PHP_EOL, 'Received part.', PHP_EOL;
				case 'last' :
					if (empty($result)) {
						$result = json_decode($content, true);
					} else {
						$result = Response::merge($result, json_decode($content, true));
					}
					break;
				default :
					echo PHP_EOL, $type, PHP_EOL, $content;
					die('EXIT');
			}
		} while ($type !== 'last');

		if ($log) echo PHP_EOL;
		if (isset($this->json)) {
			echo json_encode($result), PHP_EOL;
		} else {
			print_r($result);
		}
		if ($log) echo PHP_EOL;

		if ($log) {
			echo PHP_EOL;
			$this->out('Client done.', array('nl' => 2, 'style' => 'blue'));
			echo PHP_EOL;
		}

	}

	/**
	 * Start a logger that will echo events published by the hub
	 *  Usage: li3 zmq listen sub1[,sub2,..]
	 *   Possible subscriptions are :
	 *	request, get, post, delete, put,
	 *	status, ping, register, event
	 *
	 * Requires a connection called 'subscriber' in `config\bootstrap\connections.php`
	 *
	 * @param string $resources users
	 */
	public function listen($stuff = 'event,error') {
		$subs = explode(',', $stuff);
		$map = array(
			'post' => '!REQUEST! post',
			'get' => '!REQUEST! get',
			'put' => '!REQUEST! put',
			'delete' => '!REQUEST! delete',
		);
		$subs = array_map(function($v) use ($map) {
			if (isset($map[$v])) return $map[$v];
			else return '!'.strtoupper($v).'!';
		}, $subs);

		$context = new \ZMQContext();

		$subscriber = $this->__connection('subscriber')->connect()->socket();

		foreach ($subs as $sub) $subscriber->setSockOpt(\ZMQ::SOCKOPT_SUBSCRIBE, $sub);

		$this->out('Starting SUBSCRIPTION logger for : ', array('nl'=>0, 'style' => 'blue'));

		foreach ($subs as $sub) {
			$this->out('[', array('nl'=>0, 'style' => 'blue'));
			$this->out($sub, array('nl'=>0, 'style' => 'green'));
			$this->out(']', array('nl'=>0, 'style' => 'blue'));
		}
		$this->out(' ', array('nl'=>2, 'style' => 'blue'));

		while (true) {
			$msg = $subscriber->recv();
			$this->out(date("H:i:s"),array('nl' => 0, 'style' => 'red'));
			$this->out(' > ',array('nl' => 0, 'style' => 'blue'));
			$this->out($msg,array('nl' => 1, 'style' => 'green'));
		}
	}

	/**
	 * Get the connection called $resource
	 *
	 * @param string $connection_config
	 * @return \li3_zmq\extensions\data\source\Zeromq
	 */
	private function __connection($connection_config) {
		$responder = Connections::get($connection_config);

		/** candy **/
		if ($responder === null) {
			if (isset($this->log)) {
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
		return $responder;
	}

    protected function secondsToMilliSeconds($sec) {
        return $sec/1000;
    }
    protected function seconds($msec) {
        return $msec*1000;
    }

    protected function milliseconds($msec) {
        return $msec;
    }

    protected function microseconds($msec) {
        return $msec/1000;
    }
}
