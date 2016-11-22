<?php
/**
 * Created by PhpStorm.
 * User: outied
 * Date: 2016/11/22
 * Time: 11:29 PM
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
$swarmProcess->pushProcessOnQueue(new Process('sleep 10'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('ls noSuchFile'));
$swarmProcess->pushProcessOnQueue(new Process('ls'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 10'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 10'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 10'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 1'));
$swarmProcess->pushProcessOnQueue(new Process('sleep 10'));
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

$swarmProcess->setMaxRunStackSize(25);


$count = 0;

do {
    try {
        //Just for fun, halt operations when we filled up the stack
        if ($swarmProcess->getCurrentRunningStackCount() === $swarmProcess->getMaxRunStackSize()) {
            $swarmProcess->stopProcessing();
        }
        //Now when the stack is emptied out, start processing again.
        if ($swarmProcess->getCurrentRunningStackCount() === 0) {
            $swarmProcess->startProcessing();
        }
        $count++;
    } catch (Exception $e) {
        // do something intelligent with the exception - but do not let the loop end, you will lose work
    }
} while ($swarmProcess->tick());

