<?php

namespace Soarce\ParallelProcessDispatcher;

use Generator;

class OutputAggregator {

    /** @var Dispatcher */
    private $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return Generator|string[]|null
     */
    public function getOutput()
    {
        $this->dispatcher->tick();

        while ($this->dispatcher->hasRunningProcesses()) {
            $this->dispatcher->tick();
            yield from $this->processOneIteration();
        }
        yield from $this->processOneIteration();
    }

    /**
     * @return Generator|string[]|null
     */
    private function processOneIteration()
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
