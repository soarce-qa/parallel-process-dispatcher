<?php

namespace FastBill\ParallelProcessDispatcher;

class OutputAggregator {

    /** @var Dispatcher */
    private $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function getOutput()
    {
        // foreach over the jobs that have ouput (-> ProcessLineOutput) or are finished (->Process)

        // somehow remember the finished and processed ones to not iterate each time.

        // yield processname -> line of the log. This means re-using array keys, but it's fun! :D

        yield;
    }

}