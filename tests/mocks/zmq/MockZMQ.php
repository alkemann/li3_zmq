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
 * Mocks ZMQ to allow for testing without ZMQ binding installed
 */
class MockZMQ {

	const SOCKET_PAIR = 0 ;
	const SOCKET_PUB = 1 ;
	const SOCKET_SUB = 2 ;
	const SOCKET_REQ = 3 ;
	const SOCKET_REP = 4 ;
	const SOCKET_XREQ = 5 ;
	const SOCKET_XREP = 6 ;
	const SOCKET_UPSTREAM = 7 ;
	const SOCKET_DOWNSTREAM = 8 ;
	const SOCKET_PUSH = 7 ;
	const SOCKET_PULL = 8 ;
	const SOCKOPT_HWM = 1 ;
	const SOCKOPT_SWAP = 3 ;
	const SOCKOPT_AFFINITY = 4 ;
	const SOCKOPT_IDENTITY = 5 ;
	const SOCKOPT_SUBSCRIBE = 6 ;
	const SOCKOPT_UNSUBSCRIBE = 7 ;
	const SOCKOPT_RATE = 8 ;
	const SOCKOPT_RECOVERY_IVL = 9 ;
	const SOCKOPT_MCAST_LOOP = 10 ;
	const SOCKOPT_SNDBUF = 11 ;
	const SOCKOPT_RCVBUF = 12 ;
	const SOCKOPT_RCVMORE = 13 ;
	const SOCKOPT_TYPE = 16 ;
	const SOCKOPT_LINGER = 17 ;
	const POLL_IN = 1 ;
	const POLL_OUT = 2 ;
	const MODE_NOBLOCK = 1 ;
	const MODE_SNDMORE = 2 ;
	const DEVICE_FORWARDER = 2 ;
	const DEVICE_QUEUE = 3 ;
	const DEVICE_STREAMER = 1 ;
	const ERR_INTERNAL = -99 ;
	const ERR_EAGAIN = 11 ;
	const ERR_ENOTSUP = 95 ;
	const ERR_EFSM = 156384763 ;
	const ERR_ETERM = 156384765 ;

}
