<?php

namespace Soarce\ParallelProcessDispatcher;

class OutputAggregator {
    public function __construct(private Dispatcher $dispatcher)
    {}

    /**
     * @param  int $microseconds to sleep after an iteration over all running processes. This prevents 100% CPU usage
     * @return \Generator|string[]|null
     */
    public function getOutput($microseconds = 1000): ?\Generator
    {
        $this->dispatcher->tick();

        while ($this->dispatcher->hasRunningProcesses()) {
            $this->dispatcher->tick();
            yield from $this->processOneIteration();
            usleep($microseconds);
        }
        yield from $this->processOneIteration();
    }

    /**
     * @return \Generator|string[]|null
     */
    private function processOneIteration(): ?\Generator
    {
        foreach ($this->dispatcher->getProcessesWithPendingOutput() as $process) {
            if ($process instanceof ProcessLineOutput) {
                while ($process->hasNextOutput()) {
                    yield $process->getName() => $process->getNextOutput();
                }
            } else {
                foreach (explode("\n", $process->getOutput()) as $line) {
                    yield $process->getName() => $line . "\n";
                }
            }
        }
    }
}
