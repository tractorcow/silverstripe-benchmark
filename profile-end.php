<?php

// Stop profiling
$xhprofData = xhprof_disable();

// save raw data for this profiler run using default
// implementation of iXHProfRuns.
$xhprofRuns = new XHProfRuns_Default();
$run_id = $xhprofRuns->save_run($xhprofData, "page run");

echo "<pre>".
     "<a href='/xhprof/xhprof_html/index.php?run=$run_id&source=xhprof_foo'>".
     "View the XH GUI for this run".
     "</a>\n".
     "</pre>\n";
