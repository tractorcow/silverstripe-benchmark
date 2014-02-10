<?php

// Init benchmark
$xhprofDir = realpath(dirname(dirname(__FILE__)) .'/xhprof/xhprof_lib');
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