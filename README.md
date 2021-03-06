# soarce/parallel-process-dispatcher [![Build Status](https://travis-ci.com/soarce/parallel-process-dispatcher.svg?branch=master)](https://travis-ci.com/soarce/parallel-process-dispatcher) [![Packagist](https://img.shields.io/packagist/dt/soarce/parallel-process-dispatcher.svg)](https://packagist.org/packages/soarce/parallel-process-dispatcher)

This micro-library has two classes. One encapsulates a (linux commandline) process into an object and allows asynchronous running without deadlocks. 
The other is a multi-process-dispatcher which takes an arbitrary number of beforementioned processes and runs them simultaneously (with a maximum number of concurrent processes).

### Usage examples:
* Dispatching long running cronjobs which e.g. mostly wait for webservice responses (you can run more processes than
  max. CPUs)
* Running background workers which listen on a queue (maximum should be number of CPUs)
* Running commandline-tasks inside a web application simultaneously, e.g. PDF-Generation, Image-Processing etc.


### Installation:

Add the following to your `composer.json`:
```json
{
    "require": {
        "soarce/parallel-process-dispatcher": "*"
    }
}
```

or just run the following command in your project root directory

```sh
$ composer require "soarce/parallel-process-dispatcher"
```

## Usage

### Classes

#### Process

```php
$process = new Process('pngcrush --brute background.png');
$process->start();

// optional: do something else in your application

while (! $process->isFinished() ) {
    usleep(1000); //wait 1ms until next poll
}
echo $process->getOutput();
```

#### Dispatcher

```php
$process1 = new Process('pngcrush --brute background.png');
$process2 = new Process('pngcrush --brute welcome.png'); 
$process3 = new Process('pngcrush --brute logo.png'); 

$dispatcher = new Dispatcher(2);    // will make sure only two of those will actually run at the same time
$dispatcher->addProcess($process1);
$dispatcher->addProcess($process2);
$dispatcher->addProcess($process3);

$dispatcher->dispatch();  // this will run until all processes are finished.

$processes = $dispatcher->getFinishedProcesses();

foreach ($processes as $process) {
    echo $process->getOutput(), "\n\n";
}
```

### Advanced

#### Using Process and Dispatcher to start multiple processes and later collect the results

```php
$dispatcher = new Dispatcher(2);

$process1 = new Process('pngcrush --brute background.png');
$dispatcher->addProcess($process1, true);   // true starts the process if there are still free slots

// [... more code ...]

$process2 = new Process('pngcrush --brute welcome.png'); 
$dispatcher->addProcess($process2, true);

// [... more code ...]

// during code execution, the dispatcher cannot remove finished processes from the stack, so you have to call the tick()-function
// if you want the queue to advance - but it's optional since at latest the __destruct() function will call dispatch(); 
$dispatcher->tick();

// [... more code ...]

$dispatcher->dispatch();  // this will make the dispatcher wait until all the processes are finished, if they are still running

$processes = $dispatcher->getFinishedProcesses();

// loop over results
```


#### Reading Output from multiple jobs while they are running

A possible use case for this would be running multiple crawlers over separate slow(er) filesystems or websites to generate a list of
certain download or backup worthy files while the main process keeps track of the list, eliminates duplicates and writes the backup.

```php
$dispatcher = new Dispatcher(2);

$dispatcher->addProcess(new ProcessLineOutput("...", 'job1'));
$dispatcher->addProcess(new ProcessLineOutput("...", 'job2'));
$dispatcher->addProcess(new ProcessLineOutput("...", 'job3'));

$oa = new OutputAggregator($dispatcher);
foreach ($oa->getOutput() as $job => $line) {
    echo $job, ': ', $line;
}
```

The function `OutputAggregator::getOutput()` returns a Generator which returns the job's name (here job1-3) as key and the output
line as value.
Contrary to arrays, the key will with almost certain probability appear multiple times. 


## Known Issues

### Process

* PHP Internals: Be aware that if the child process produces output, it will write into a buffer until the buffer is
full. If the buffer is full the child pauses until the parent reads from the buffer and makes more room. This is done
in the isFinished() method. The dispatcher calls this method periodically to prevent a deadlock. If you use the process
class standalone, you have multiple possibilities to prevent this:
  * call isFinished() yourself in either a loop, using a tick function or otherwise during execution of your script
  * instead of writing to stdOut, divert output to a temporary file and use its name as output.
  * use the combination of OutputAggregator and ProcessLineOutput and work on output as soon as it arrives. This
    might consume a lot of RAM for buffers if your jobs generate a lot of output, so set high enough limits.
  
### Dispatcher

* Multiple dispatchers (in different processes) are not aware of each other. So if you have a script that uses a
dispatcher to call another script which itself uses a dispatcher to spawn multiple processes, you will end up with more
child processes than the maximum, so choose the maximum accordingly or use a queue (e.g. Redis) and make the workers
aware of each other by e.g. registering in a redis-stack for running workers.
