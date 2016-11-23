<?php
/**
 * User: sarel
 * Date: 2015/12/29
 * Time: 17:48
 */

namespace Afrihost\SwarmProcess;

use Symfony\Component\Process\Process;

class SwarmProcess extends SwarmProcessBase
{
    /** @var int */
    protected $maxRunStackSize = 10;

    /** @var array */
    protected $queue = array();

    /** @var array */
    private $currentRunningStack = array();

    /** @var int */
    private $runningProcessKeyTracker = 0;
    /** @var array */
    protected $failedCommands = array();
    /**
     * @var bool
     * This is used to halt processing
     */
    protected $keepGoing = true;
    /**
     * @var bool
     * This is used to determine if we will auto adjust max run stack size
     */
    protected $autoScaleProcesses = false;
    /**
     * @var int
     * The minimum amount that we will drop $maxRunStackSize to
     */
    protected $scalingLowerBound = 0;
    /**
     * @var
     * The maximum amount that we will raise $maxRunStackSize to
     * int  */
    protected $scalingUpperBound = 0;
    /**
     * @var int
     * Amount of processes completed
     */
    protected $amountCompleted = 0;

    /**
     * Runs all the processes, not going over the maxRunStackSize, and continuing until all processes in the processingStack has run their course.
     *
     * @param callable $moreWorkToAddCallable
     * @param callable $shouldContinueRunningCallable
     */
    public function run(callable $moreWorkToAddCallable = null, callable $shouldContinueRunningCallable = null)
    {
        $this->runningProcessKeyTracker = 0; // seed the key

        // As long as we have more thing we can do, do them:
        do {
            // Check if the user specified a process adder callable:
            if (is_callable($moreWorkToAddCallable)) {
                // As long as the callable returns us a process to add, we'll add more. It's up to the user to limit this.
                while ($p = call_user_func($moreWorkToAddCallable)) {
                    $this->pushProcessOnQueue($p);
                }
            }
        } while ($this->tick() || (is_callable($shouldContinueRunningCallable) ? call_user_func($shouldContinueRunningCallable) : false));
    }

    /**
     * Does the necessary work to figure out whether a process is done and should be removed from the runningStack as well as adding the next process(es) in line into empty slot(s)
     * If there's more work to be done at the end of the method, tick returns true (so you can use it in your while loop)
     */
    public function tick()
    {
        // If we have an open slot, use it :
        // Additionally, test if we are asked to halt processing
        while ($this->haveRunningSlotsAvailable() && (count($this->queue) > 0 && $this->keepGoing)) {
            /** @var Process $tmpProcess */
            $tmpProcess = array_shift($this->queue);
            $tmpProcess->start();
            $this->currentRunningStack[++$this->runningProcessKeyTracker] = $tmpProcess;
            $this->logger->info('+ Started Process ' . $this->runningProcessKeyTracker . ' [' . $tmpProcess->getCommandLine() . ']');
        }

        // Loop through the running things to check if they're done:
        foreach ($this->currentRunningStack as $runningProcessKey => $runningProcess) {
            /** @var $runningProcess Process */
            if (!$runningProcess->isRunning()) {
                //If the exit code is not 0 (OK || SUCCESS), then log it.
                if ($runningProcess->getExitCode() !== 0) {
                    $this->failedCommands[] = [
                        $runningProcess->getCommandLine(),
                        $runningProcess->getExitCode(),
                        $runningProcess->getExitCodeText(),
                    ];
                }
                $logMessage = '- Removed Process ' . $runningProcessKey . ' from currentRunningStack - ' .
                    'ExitCode:' . $runningProcess->getExitCode() . '(' . $runningProcess->getExitCodeText() . ') ' .
                    '[' . count($this->queue) . ' left in queue]';
                unset($this->currentRunningStack[$runningProcessKey]);
                $this->amountCompleted++;
                $this->logger->info($logMessage);
                //If the queue is empty, no need to check anything.
                //Recalibrate maxStackSize after a process is freed up
                if ($this->getStackCount() !== 0 && $this->autoScaleProcesses) {
                    $this->autoScale();
                }
            }
        }

        return ((count($this->queue) > 0) || count($this->currentRunningStack) > 0);
    }

    /**
     * Returns true/false whether we have slots available to add more jobs in concurrency
     *
     * @return bool
     */
    protected function haveRunningSlotsAvailable()
    {
        return (count($this->currentRunningStack) < $this->maxRunStackSize);
    }

    /**
     * Returns the number of elements still left to do on the queue
     *
     * @return int
     */
    public function getStackCount()
    {
        return count($this->queue);
    }

    /**
     * Returns the number of currently running processes
     *
     * @return int
     */
    public function getCurrentRunningStackCount()
    {
        return count($this->currentRunningStack);
    }

    /**
     * Pushes a native command, ex "ls -lahtr" on the processing stack after converting it to a Process object
     *
     * @param string $cmd
     * @return SwarmProcess
     */
    public function pushNativeCommandOnQueue($cmd)
    {
        $tmp = new Process($cmd);

        return $this->pushProcessOnQueue($tmp);
    }

    /**
     * Pushes a Process object on to the processing stack
     *
     * @param Process $process
     * @return $this
     */
    public function pushProcessOnQueue(Process $process)
    {
        $this->queue[] = $process;

        $this->logger->debug('Process pushed on to stack. Stack size: ' . count($this->queue));

        return $this;
    }

    /**
     * Gets the maximum number of processes that can be run at the same time (concurrently)
     *
     * @return int
     */
    public function getMaxRunStackSize()
    {
        return $this->maxRunStackSize;
    }

    /**
     * Set the maximum number of processes that can be run at the same time (concurrently)
     *
     * @param int $maxRunStackSize
     *
     * @return SwarmProcess
     * @throws \OutOfBoundsException
     */
    public function setMaxRunStackSize($maxRunStackSize)
    {
        if ($maxRunStackSize <= 0) {
            throw new \OutOfBoundsException('You many not have a maxRunStack size less or equal to 0. You gave: "' . $maxRunStackSize . '"');
        }

        $this->maxRunStackSize = $maxRunStackSize;

        $this->logger->debug('$maxRunStackSize changed to ' . $maxRunStackSize);

        return $this;
    }

    /**
     * return the logged failed commands
     */
    public function getFailedCommands()
    {
        return $this->failedCommands;
    }

    /**
     * Puts a halt on any new processes being loaded from the queue
     */
    public function stopProcessing()
    {
        //If it's already set to not process, do nothing.
        if ($this->keepGoing) {
            $this->logger->info("Not loading new processes from queue");
            $this->keepGoing = false;
        }
    }

    /**
     * Removes the halt on any new processes being loaded from the queue
     */
    public function startProcessing()
    {
        //If it's already set to procss, do nothing
        If (!$this->keepGoing) {
            $this->logger->info("Loading new processes from queue");
            $this->keepGoing = true;
        }
    }

    /**
     * This function uses load average to determine how busy the CPU is.
     * It then increases or decreases the amount of available job slots accordingly.
     * It aims to have CPU load between 80% and 95% based on load average
     */
    private function autoScale()
    {
        /*Later I can use IO speed to determine when I'm hammering the hard drive and use that as well.
         *Another cute thing to try is to have the user give us an idea of processes to be loaded
         *i.e. They set that most commands are CPU bound -> use CPU based checks
         * They also give IO bound flags -> also check IO
         * They give a limit on RAM usage -> check memory size adjust load accordingly
         *
         * For now, just CPU checks should do.
         */

        /*
         * This is a *NIX solution.
         * I unfortunately don't have a windows machine available to test Windows CPU load on.
         *
        */
        if (stristr(PHP_OS, 'win')) {
            $this->setAutoStackSizeScaling(false);
            return;
        } else {
            //Get the lead average
            $exec_loads = sys_getloadavg();
            //Gets amount of processors
            $coreCount = trim(shell_exec("grep -P '^processor' /proc/cpuinfo|wc -l"));
            //Get the load average as a percentage;
            $cpu = round($exec_loads[1] / ($coreCount + 1) * 100, 0);
        }
        //Only change the stack size if the process is running
        if ($this->keepGoing) {
            $this->logger->debug("Average CPU load average:  $cpu%");
            if ($cpu > 85) {
                $this->decreaseMaxRunStackSize(1);
            } elseif ($cpu < 40) {
                //If CPU load isn't very high, increase the RunStackSize quicker
                $this->increaseMaxRunStackSize(5);
            } elseif ($cpu < 75) {
                $this->increaseMaxRunStackSize(1);
            }
        }
    }


    /**
     * @param $autoscale
     */
    public function setAutoStackSizeScaling($autoscale)
    {
        if (is_bool($autoscale)) {
            $this->autoScaleProcesses = $autoscale;
            $this->logger->debug("Auto process scaling set to " . ($autoscale ? "TRUE" : "FALSE"));
        } else {
            throw new \InvalidArgumentException('Expected a boolean value');
        }
    }

    /**
     * @param $increase
     */
    public function increaseMaxRunStackSize($increase)
    {
        if ($increase > 0) {
            $newSize = $this->getMaxRunStackSize() + $increase;
            if ($newSize > $this->getScalingUpperBound()) {
                //if we want to set the runStackSize to more than the limit, set the $newSize to the limit.
                $newSize = $this->getScalingUpperBound();
            }
            if ($newSize < $this->getScalingUpperBound()) {
                //If $newSize is smaller than the upper limit, set the new stack size
                $this->setMaxRunStackSize($newSize);
                $this->logger->debug('$maxRunStackSize raised by ' . $increase . ' to ' . $this->getMaxRunStackSize());
            } elseif ($newSize == $this->getScalingUpperBound() && $newSize > $this->getMaxRunStackSize()) {
                //If the $newSize matches the limit, and the $newSize is more than the current amount of processes
                //Set it to the upper bound, maxing out available processes.
                $this->setMaxRunStackSize($this->getScalingUpperBound());
            } elseif ($newSize < $this->getMaxRunStackSize()) {
                //If we get here $newSize is as big as it can be, but still smaller than the runStackSize.
                //Lower RunStackSize to what we are told the process limit is
                $this->setMaxRunStackSize($this->getScalingUpperBound());
                $this->logger->debug('$maxRunStackSize was above upper bound, lowered to ' . $this->getScalingUpperBound());
            } else {
                $this->logger->debug('Reached the upper bound for RunStackSize which is ' . $this->getScalingUpperBound());
            }

        } else {
            throw new \InvalidArgumentException('The increase amount needs to be bigger than 0');
        }
    }

    /**
     * @param $decrease
     */
    public function decreaseMaxRunStackSize($decrease)
    {
        if ($decrease > 0) {
            $newSize = $this->getMaxRunStackSize() - $decrease;
            if ($newSize < $this->getScalingLowerBound()) {
                //if we want to set the stack size less than the limit, set the $newSize to the limit.
                $newSize = $this->getScalingLowerBound();
            }
            if ($newSize > $this->getScalingLowerBound()) {
                //If $newSize is larger than the lower limit, set the new stack size
                $this->setMaxRunStackSize($newSize);
                $this->logger->debug('$maxRunStackSize lowered by ' . $decrease . ' to ' . $this->getMaxRunStackSize());
            } elseif ($newSize == $this->getScalingLowerBound() && $newSize < $this->getMaxRunStackSize()) {
                //If the $newSize matches the limit, and the $newSize is less than the current amount of processes
                //Set it to the lower bound, bottoming out available processes.
                $this->setMaxRunStackSize($this->getScalingLowerBound());
                $this->logger->debug('$maxRunStackSize was below lower bound, raised to ' . $this->getScalingLowerBound());
            } elseif ($newSize > $this->getMaxRunStackSize()) {
                //If we get here $newSize is as small as it can be, but still bigger than the runStackSize.
                //Raise RunStackSize to what we are told the process limit is
                $this->setMaxRunStackSize($this->getScalingLowerBound());
                $this->logger->debug('$maxRunStackSize was below lower bound, raised to ' . $this->getScalingLowerBound());
            } else {
                $this->logger->debug('Reached the lower bound for RunStackSize which is ' . $this->getScalingLowerBound());
            }

        } else {
            throw new \InvalidArgumentException('The decrease amount needs to be bigger than 0');
        }
    }

    /**
     * @param $min
     * @param $max
     * @return $this
     */
    public function setScalingBounds($min, $max)
    {
        //Sanity checks
        if (!$min || !$max) {
            throw new \InvalidArgumentException('Both upper and lower bounds need to be given');
        } elseif ($min <= 0) {
            throw new \OutOfBoundsException("You many not have a minimum RunStack size less or equal to 0. You gave: $min");
        } elseif ($max <= 0) {
            throw new \OutOfBoundsException("You many not have a maximum RunStack size less or equal to 0. You gave: $max");
        } elseif ($min > $max) {
            throw new \InvalidArgumentException('You may not set the minimum RunStack size to more than the maximum Runstack size');
        }
        $this->scalingLowerBound = $min;
        $this->scalingUpperBound = $max;
        $this->logger->debug('$scalingLowerBound changed to ' . $min);
        $this->logger->debug('$scalingUpperBound changed to ' . $max);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getScalingLowerBound()
    {
        return $this->scalingLowerBound;
    }

    /**
     *
     * @return mixed
     */
    public function getScalingUpperBound()
    {
        return $this->scalingUpperBound;
    }

    /**
     * Simply returns how many processes have been completed
     * @return int
     */
    public function getAmountCompleted()
    {
        return $this->amountCompleted;
    }

    public function stopAndReturnUnfinished()
    {
        //Stop adding new things to the processing queue
        $this->keepGoing = false;
        //Copy the queue that is still to be done
        $queue = $this->queue;
        //Clear it so tick() will stop when it's done with the current lot
        $this->queue = [];
        $this->logger->debug('Stopping processing and returning unfinished processes');
        //Return the copy of the unfinished processes
        return $queue;
    }

}
