<?php
/**
 * Created by PhpStorm.
 * User: outied
 * Date: 2016/11/22
 * Time: 7:49 PM
 */
/**
 * I need to add comments :)
 */
use Afrihost\SwarmProcess\SwarmProcess;
use Monolog\Logger;
use Symfony\Component\Process\Process;

chdir(__DIR__);
require('../vendor/autoload.php');

$logger = new Logger('swarm_logger');

$swarmProcess = new SwarmProcess($logger);

// Add a few things to do:
//$swarmProcess->pushProcessOnQueue(new Process('sleep 9'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('ls noSuchFile'));
$swarmProcess->pushProcessOnQueue(new Process('ls'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 8'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 7'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 6'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 5'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 5'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 4'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 3'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 2'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));

$swarmProcess->setMaxRunStackSize(1);
//Some new stuff.
//Setting if the maxRunStackSize should scale using CPU load as a gauge.
$swarmProcess->setAutoStackSizeScaling(true);
$swarmProcess->getScalingUpperBound();
$swarmProcess->setScalingBounds(5,7);

$swarmProcess->run();
//Grab any commands that failed and log them
foreach ($swarmProcess->getFailedCommands() as $failure) {
    $logger->debug("Command that failed: $failure[0]");
    $logger->debug("Command failure code: $failure[1]");
    $logger->debug("Command failure text: $failure[2]");
}