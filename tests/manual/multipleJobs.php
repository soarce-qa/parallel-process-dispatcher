<?php

use FastBill\ParallelProcessDispatcher\ProcessLineOutput;
use FastBill\ParallelProcessDispatcher\Process;
use FastBill\ParallelProcessDispatcher\Dispatcher;
use FastBill\ParallelProcessDispatcher\OutputAggregator;

include '../../src/Process.php';
include '../../src/ProcessLineOutput.php';
include '../../src/Dispatcher.php';
include '../../src/OutputAggregator.php';

$fullPath = __DIR__ . '/outputLines.php';
$dispatcher = new Dispatcher(2);
$dispatcher->addProcess(new ProcessLineOutput("php -f $fullPath 10", 'job1'));
$dispatcher->addProcess(new Process          ("php -f $fullPath 10", 'job2'));
$dispatcher->addProcess(new ProcessLineOutput("php -f $fullPath 10", 'job3'));
$dispatcher->addProcess(new ProcessLineOutput("php -f $fullPath 10", 'job4'));

$oa = new OutputAggregator($dispatcher);

foreach ($oa as $job => $line) {
    echo $job, ': ', $line;
}
