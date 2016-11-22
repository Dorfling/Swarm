<?php
/**
 * Created by PhpStorm.
 * User: outied
 * Date: 2016/11/22
 * Time: 11:52 PM
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
$swarmProcess->pushProcessOnQueue(new Process('ls *'));

$swarmProcess->setMaxRunStackSize(2);


$count = 0;

do {
    try {
        //Just for fun, if 2 are done stop operations and give back what hasn't been processed
        if ($swarmProcess->getAmountCompleted()=== 2 && $swarmProcess->getStackCount() !==0) {
            echo $swarmProcess->getStackCount();
            foreach ($swarmProcess->stopAndReturnUnfinished() as $unfinished) {
                $logger->debug("Unfunished command: ".$unfinished->getCommandLine());
            }
        }
    } catch (Exception $e) {
        // do something intelligent with the exception - but do not let the loop end, you will lose work
    }
} while ($swarmProcess->tick());

