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

		echo 'y';

		$status = proc_get_status($this->process);
		echo 'z';

		if ($status['running']) {
		    echo 'a';
			if (! $this->nonblockingMode) {
				stream_set_blocking($this->stdout, false);
				stream_set_blocking($this->stderr, false);
				$this->nonblockingMode = true;
			}
            echo 'b';
            $this->readOutputIntoArray();
            echo 'k';
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
        echo "c";
        $temp = stream_get_contents($this->stdout);
        echo "d";
        if ($temp === '') {
            return;
        }
        echo "e";

        $tempArr = explode("\n", $temp);
        if (count($tempArr) === 1) {
            $this->remainder .= $tempArr[0];
            return;
        }

        $this->output[] = $this->remainder . array_shift($tempArr);
        $this->remainder = array_pop($tempArr);

        foreach ($tempArr as $row) {
            $this->output[] = $row;
        }
    }
}