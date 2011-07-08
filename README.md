Li3_ZMQ - Lithium meets 0MQ
---------------------------

Use ZeroMQ as datasource for Lithium models to access remote data.

Run console commands for running the app as a 0mq resource provider or
access the 0mq network with the client command!

---

The libraries folder contains a submodule that is NOT required to be loaded
to use this module, but in it, there are stand-alone console php scripts
that can be run as part of the ZeroMQ network. Most importantly, the hub.php
script is the one that is executed to be available to the service and client
console commands to talk to.

You can fire it up by going to <li3_zmq plugin path>/libraries/zeromq_poc/ and
run 'php hub.php'.

