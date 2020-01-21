<?php

use FastBill\ParallelProcessDispatcher\ProcessLineOutput;

include '../../src/Process.php';
include '../../src/ProcessLineOutput.php';

$fullPath = __DIR__ . '/outputLines.php';
$job = new ProcessLineOutput("php -f $fullPath", "whatever");
$job->start();

echo 'x';

while (!$job->isFinished()) {
    while ($job->hasNextOutput()) {
        echo $job->getNextOutput() . "\n";
    }
    echo "xxx --- done with batch, sleeping a sec.\n";
    sleep(1);
}

