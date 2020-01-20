<?php

namespace FastBill\ParallelProcessDispatcher;

/**
 * Class Process
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
			if (! $this->nonblockingMode) {
				stream_set_blocking($this->stdout, false);
				stream_set_blocking($this->stderr, false);
				$this->nonblockingMode = true;
			}

            $this->readOutputIntoArray();
            $this->errorOutput .= stream_get_contents($this->stderr);
			return false;
		}

		if ($this->statusCode === null) {
			$this->statusCode = (int) $status['exitcode'];
		}

		// Process remainder of outputs
		$this->readOutputIntoArray();
		fclose($this->stdout);

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
	 * @return string
	 * @throws \RuntimeException
	 */
	public function getOutput()
	{
		throw new \RuntimeException("Cannot get output in total, use (get|has)NextOutput()");
	}

    /**
     * @return bool
     */
	public function hasNextOutput()
    {
        $this->readOutputIntoArray();
        return count($this->output) > 0;
    }

    /**
     * @return string
     */
    public function getNextOutput()
    {
        $this->readOutputIntoArray();
        return array_shift($this->output);
    }

    /**
     * @return void
     */
    private function readOutputIntoArray()
    {
        while (!feof($this->stdout)) {
            $temp = fgets($this->stdout);
            if (substr($temp, -1) === "\n") {
                $this->output[] = $this->remainder . $temp;
                $this->remainder = '';
            } else {
                $this->remainder .= $temp;
            }
        }
    }
}