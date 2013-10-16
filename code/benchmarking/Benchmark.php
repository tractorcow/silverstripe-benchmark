<?php

class SS_Benchmark extends SapphireTest {
	
	protected $iterations = 50;
	
	protected $suite = null;
	
	public function setSuite(BenchmarkSuite $suite) {
		$this->suite = $suite;
	}
	
	public function benchmark($description, $callable) {
		$time = 0.0;
		
		for($i = 0; $i < $this->iterations; $i++) {	
			$startTime = microtime(true);
			$callable();
			$endTime = microtime(true);
			$time += ($endTime-$startTime);
		}
		
		$average = round($time / $this->iterations, 6);
		$this->suite->ReportMark($description, $average, $this->iterations);
	}
	
	/**
	 * Profile a function call
	 * Requires the following:
	 * - xhprof PHP module
	 * - xhprof reporting module from https://github.com/preinheimer/xhprof
	 * 
	 * @param type $description
	 * @param type $callable
	 * @return type
	 */
	public function profile($description, $callable) {
		if(!function_exists('xhprof_enable')) {
			$this->suite->Message('xhprof not installed');
			return;
		}
		
		$xhprofDir = realpath(BASE_PATH .'/xhprof/xhprof_lib');
		if(empty($xhprofDir)) {
			$this->suite->Message('xhprof logging tool https://github.com/preinheimer/xhprof required');
			return;
		}
		if(!defined('XHPROF_LIB_ROOT')) define('XHPROF_LIB_ROOT', $xhprofDir);
		require_once $xhprofDir . "/config.php";
		require_once $xhprofDir . "/utils/xhprof_lib.php";
		require_once $xhprofDir . "/utils/xhprof_runs.php";
		
		// start profiling
		xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
		$callable();
		
		// Stop profiling
		$xhprofData = xhprof_disable();

		// save raw data for this profiler run using default
		// implementation of iXHProfRuns.
		$xhprofRuns = new XHProfRuns_Default();
		$runID = $xhprofRuns->save_run($xhprofData, "benchmark");
		$url = Director::absoluteURL("/xhprof/xhprof_html/index.php?run={$runID}&source=benchmark");
		
		if(Director::is_cli()){
			$this->suite->Message("$description: Benchmark available at $url");
		} else {
			echo "<p><a href='$url'>$message - Click here for benchmark data</a></p>\n";
		}
		
	}
}
