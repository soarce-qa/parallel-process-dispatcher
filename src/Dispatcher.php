<?php

namespace Soarce\ParallelProcessDispatcher;

class Dispatcher
{
	/** @var Process[] */
	private $processQueue = [];

	/** @var Process[] */
	private $runningProcesses = [];

	/** @var Process[] */
	private $finishedProcesses = [];

	/** @var Process[] */
    private $finishedProcessesWithOutput = [];

    /** @var bool if set to false, finished processes will not be moved to "finishedProcesses* stack. */
    private $preserveFinishedProcesses = true;

	public function __construct(private int $maxProcesses = 2)
	{
		if ($maxProcesses < 1) {
			throw new \InvalidArgumentException('number of processes must be at least 1');
		}
	}

    /**
     * @param bool $preserveFinishedProcesses
     */
    public function setPreserveFinishedProcesses(bool $preserveFinishedProcesses): void
    {
        $this->preserveFinishedProcesses = $preserveFinishedProcesses;
    }

	/**
	 * @param Process $process
	 * @param boolean $start if $start is true, after pushing the process to the queue, the running-processes-stack is checked for finished jobs and new
	 *                       ones will be taken from the queue until the maximum is reached.
	 */
	public function addProcess(Process $process, bool $start = false): void
    {
		$this->processQueue[] = $process;

		if ($start) {
			$this->tick();
		}
	}

	/**
	 * this works over the whole queue and starts maxProcesses processes in parallel.
	 * returns if all are through.
	 * @param int $checkIntervalMicroseconds
	 */
	public function dispatch(int $checkIntervalMicroseconds = 1000): void
    {
		while ($this->hasProcessesInQueue() || $this->hasRunningProcesses()) {
			$this->fillRunningStackAndStartJobs();
			usleep($checkIntervalMicroseconds / 2);
			$this->checkAndRemoveFinishedProcessesFromStack();
			usleep($checkIntervalMicroseconds / 2);
		}
	}

	/**
	 * advances the queue without blocking - this can/should be run from time to time to flush buffers
	 * and start more jobs if others had finished.
	 */
	public function tick(): void
    {
		$this->checkAndRemoveFinishedProcessesFromStack();
		$this->fillRunningStackAndStartJobs();
		$this->checkAndRemoveFinishedProcessesFromStack();
	}

	public function hasProcessesInQueue(): bool
    {
		return count($this->processQueue) > 0;
	}

	public function hasRunningProcesses(): bool
    {
		return count($this->runningProcesses) > 0;
	}

	/**
	 * @return Process[]
	 */
	public function getFinishedProcesses(): array
    {
		return $this->finishedProcesses;
	}

	protected function checkAndRemoveFinishedProcessesFromStack(): void
    {
		// check all running processes if they are still running,
		$finishedProcIds = [];
		foreach ($this->runningProcesses as $key => $runningProc) {
			// if one is finished, move to finishedProcesses
			if ($runningProc->isFinished()) {
				$finishedProcIds[] = $key;
				if ($this->preserveFinishedProcesses) {
				    $this->finishedProcesses[] = $runningProc;
                }
				$this->finishedProcessesWithOutput[] = $runningProc;
			}
		}

		// remove the finished ones from the running stack (has to be outside of loop
		foreach ($finishedProcIds as $procId) {
			unset ($this->runningProcesses[$procId]);
		}
	}

	protected function fillRunningStackAndStartJobs(): void
    {
		while ($this->hasProcessesInQueue() && count($this->runningProcesses) < $this->maxProcesses) {
			// get process from queue
			/** @var Process $proc */
			$proc = array_shift($this->processQueue);

			// start process
			$proc->start();

			// move to runningStack
			$this->runningProcesses[] = $proc;

			usleep (100);
		}
	}

    /**
     * @return \Generator|Process[]
     */
	public function getProcessesWithPendingOutput()
    {
        while ($this->finishedProcessesWithOutput !== []) {
            yield array_shift($this->finishedProcessesWithOutput);
        }
        foreach ($this->runningProcesses as $process) {
            if (($process instanceof ProcessLineOutput) && $process->hasNextOutput()) {
                yield $process;
            }
        }
    }

	/**
	 * This lets the running processes finish after the main program went through.
	 */
	public function __destruct()
	{
		$this->dispatch();
	}
}
