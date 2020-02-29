<?php

namespace Soarce\ParallelProcessDispatcher;

use RuntimeException;

/**
 * Class ProcessLineOutput
 *
 * represents a process (commandline-call) for multithreading. wraps popen(), inspired bei jakub-onderka/parallel-lint
 * This version is for reading output line by line while the job is running!
 *
 * @package FastBill\TraceAnalyzer
 */
class ProcessLineOutput extends Process
{
    /** @var string */
    protected $remainder = '';

	/** @var string[] */
	protected $output = [];

    public function start($stdInInput = null)
    {
        parent::start($stdInInput);

        stream_set_blocking($this->stdout, false);
        stream_set_blocking($this->stderr, false);
        $this->nonblockingMode = true;
    }

    /**
     * @return bool
     */
	public function isFinished()
	{
		if ($this->statusCode !== null) {
			return true;
		}

		$status = proc_get_status($this->process);

		if ($status['running']) {
            $this->readOutputIntoArray();
            $this->errorOutput .= stream_get_contents($this->stderr);
			return false;
		}

		// Process remainder of outputs
		$this->readOutputIntoArray();
		fclose($this->stdout);

		if ($this->statusCode === null) {
			$this->statusCode = (int) $status['exitcode'];
		}

		$this->errorOutput .= stream_get_contents($this->stderr);
		fclose($this->stderr);

		$statusCode = proc_close($this->process);

		if ($this->statusCode === null) {
			$this->statusCode = $statusCode;
		}

		$this->process = null;

		return true;
	}

	/**
	 * @throws RuntimeException
	 */
	public function getOutput()
    {
		throw new RuntimeException('Cannot get output in total, use (get|has)NextOutput()');
	}

    /**
     * @return bool
     */
	public function hasNextOutput()
    {
        $this->readOutputIntoArray();
        return count($this->output) > 0 || ($this->statusCode !== null && $this->remainder !== '');
    }

    /**
     * @return string
     */
    public function getNextOutput()
    {
        $this->readOutputIntoArray();

        if ($this->output === [] && $this->remainder !== '' ) {
            $remainder = $this->remainder;
            $this->remainder = '';
            return $remainder;
        }
        return array_shift($this->output);
    }

    /**
     * @return void
     */
    private function readOutputIntoArray()
    {
        if ($this->statusCode !== null) {
            return;
        }

        $temp = stream_get_contents($this->stdout);
        if ($temp === '') {
            return;
        }

        $tempArr = explode("\n", $temp);
        if (count($tempArr) === 1) {
            $this->remainder .= $tempArr[0];
            return;
        }

        $this->output[] = $this->remainder . array_shift($tempArr) . "\n";
        $this->remainder = array_pop($tempArr);

        foreach ($tempArr as $row) {
            $this->output[] = $row . "\n";
        }
    }
}
