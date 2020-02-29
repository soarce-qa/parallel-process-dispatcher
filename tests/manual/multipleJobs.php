<?php

use Soarce\ParallelProcessDispatcher\ProcessLineOutput;
use Soarce\ParallelProcessDispatcher\Process;
use Soarce\ParallelProcessDispatcher\Dispatcher;
use Soarce\ParallelProcessDispatcher\OutputAggregator;

include '../../src/Process.php';
include '../../src/ProcessLineOutput.php';
include '../../src/Dispatcher.php';
include '../../src/OutputAggregator.php';

$fullPath = __DIR__ . '/outputLines.php';
$dispatcher = new Dispatcher(2);

$dispatcher->addProcess(new ProcessLineOutput("php -f $fullPath 30", 'job1'));
$dispatcher->addProcess(new Process          ("php -f $fullPath 10", 'job2'));  // this will deliver output only when completely finished, but that's fine
$dispatcher->addProcess(new ProcessLineOutput("php -f $fullPath 20", 'job3'));
$dispatcher->addProcess(new ProcessLineOutput("php -f $fullPath 10", 'job4'));

$oa = new OutputAggregator($dispatcher);
foreach ($oa->getOutput() as $job => $line) {
    echo $job, ': ', $line;
}
