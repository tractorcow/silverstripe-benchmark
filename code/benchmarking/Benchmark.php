<?php

class SS_Benchmark {
	
	protected $iterations = 1000;
	
	protected $suite = null;
	
	public function setUpOnce() {}
	
	public function setUp() {}
	
	public function tearDown() {}
	
	public function tearDownOnce() {}
	
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
}
