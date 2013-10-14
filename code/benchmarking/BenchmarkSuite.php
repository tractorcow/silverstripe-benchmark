<?php

class BenchmarkSuite {
	
	protected $benchmarks = array();
	
	/**
	 *
	 * @var DebugView
	 */
	protected $reporter = null;
	
	public function __construct(DebugView $reporter) {
		$this->reporter = $reporter;
	}
	
	public function addBenchmark(SS_Benchmark $benchmark) {
		$this->benchmarks[] = $benchmark;
		$benchmark->setSuite($this);
	}
	
	public function runBenchmarks() {
		foreach($this->benchmarks as $benchmark) {
			
			// Setup benchmark
			$benchmark->setUpOnce();
			
			// Execute all benchmark* methods
			$reflection = new ReflectionClass($benchmark);
			foreach($reflection->getMethods() as $method) {
				if(preg_match('/^benchmark[A-Z].*/', $method->name)) {
					$this->reporter->writeInfo($method->class, $method->name);
					$benchmark->setUp();
					$benchmark->{$method->name}();
					$benchmark->tearDown();
				}
			}
			
			// Tear down
			$benchmark->tearDownOnce();
		}
	}
	
	public function ReportMark($description, $average, $iterations) {
		$result = "$description: $average seconds ($iterations iterations)";
		if(Director::is_cli()) echo "$result\n";
		else echo "<p>$result</p>\n";
	}
}
