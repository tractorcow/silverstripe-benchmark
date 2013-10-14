<?php

class DevelopmentAdminExtension extends Extension {
	
	private static $allowed_actions = array('benchmarks');
	
	public function benchmarks($request) {
		return BenchmarkRunner::create();
	}
}
