<?php

/**
 * Description of BenchmarkRunner
 *
 * @author Damo
 */
class BenchmarkRunner extends Controller {

	/** @ignore */
	private static $default_reporter;
	
	private static $url_handlers = array(
		'' => 'browse',
		'all' => 'all',
		'$Benchmark' => 'only'
	);
	
	private static $allowed_actions = array(
		'index',
		'browse',
		'all',
		'only'
	);
	
	/**
	 * Override the default reporter with a custom configured subclass.
	 *
	 * @param string $reporter
	 */
	public static function set_reporter($reporter) {
		if (is_string($reporter)) $reporter = new $reporter;
		self::$default_reporter = $reporter;
	}

	public function init() {
		parent::init();
		
		$canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
		if(!$canAccess) return Security::permissionFailure($this);
		
		if (!self::$default_reporter) self::set_reporter(Director::is_cli() ? 'CliDebugView' : 'DebugView');
		
		if(!isset($_GET['flush']) || !$_GET['flush']) {
			Debug::message(
				"WARNING: Manifest not flushed. " .
				"Add flush=1 as an argument to discover new classes or files.\n",
				false
			);
		}
	}
	
	public function Link() {
		return Controller::join_links(Director::absoluteBaseURL(), 'dev/benchmarks/');
	}
	
	/**
	 * Run test classes that should be run with every commit.
	 * Currently excludes PhpSyntaxTest
	 */
	public function all($request) {
		$benchmarks = ClassInfo::subclassesFor('SS_Benchmark');
		array_shift($benchmarks);
		
		foreach($benchmarks as $class => $v) {
			$reflection = new ReflectionClass($class);
			if(!$reflection->isInstantiable()) unset($benchmarks[$class]);
		}

		$this->runBenchmarks($benchmarks);
	}
	
	/**
	 * Browse all enabled test cases in the environment
	 */
	public function browse() {
		self::$default_reporter->writeHeader();
		self::$default_reporter->writeInfo('Available Benchmarks', false);
		$benchmarks = ClassInfo::subclassesFor('SS_Benchmark');
		if(Director::is_cli()) {
			$relativeLink = Director::makeRelative($this->Link());
			echo "sake {$relativeLink}all: Run all " . count($benchmarks) . " benchmarks\n";
			foreach ($benchmarks as $benchmark) {
				echo "sake {$relativeLink}$benchmark: Run $benchmark\n";
			}
		} else {
			echo '<div class="trace">';
			asort($benchmarks);
			echo "<h3><a href=\"" . $this->Link() . "all\">Run all " . count($benchmarks) . " benchmarks\n</a></h3>";
			echo "<hr />";
			foreach ($benchmarks as $benchmark) {
				echo "<h3><a href=\"" . $this->Link() . "$benchmark\">Run $benchmark</a></h3>";
			}
			echo '</div>';
		}
		
		self::$default_reporter->writeFooter();
	}
		
	/**
	 * Run only a single test class or a comma-separated list of tests
	 */
	public function only($request) {
		if($request->param('Benchmark') == 'all') {
			$this->all();
		} else {
			$classNames = explode(',', $request->param('Benchmark'));
			foreach($classNames as $className) {
				if(!class_exists($className) || !is_subclass_of($className, 'SS_Benchmark')) {
					user_error("BenchmarkRunner::only(): Invalid Benchmark '$className', cannot find matching class",
						E_USER_ERROR);
				}
			}
			
			$this->runBenchmarks($classNames);
		}
	}

	/**
	 * @param array $classList
	 * @param boolean $coverage
	 */
	public function runBenchmarks($classList, $coverage = false) {
		$startTime = microtime(true);

		// disable xdebug, as it messes up test execution
		if(function_exists('xdebug_disable')) xdebug_disable();

		ini_set('max_execution_time', 0);

		// Optionally skip certain tests
		$skipBenchmarks = array();
		if($skip = $this->request->getVar('SkipBenchmarks')) {
			$skipBenchmarks = explode(',', $skip);
		}

		$abstractClasses = array();
		foreach($classList as $className) {
			// Ensure that the autoloader pulls in the test class, as PHPUnit won't know how to do this.
			class_exists($className);
			$reflection = new ReflectionClass($className);
			if ($reflection->isAbstract()) {
				array_push($abstractClasses, $className);
			}
		}
		
		$classList = array_diff($classList, $skipBenchmarks, $abstractClasses);

		// run tests before outputting anything to the client
		$suite = new BenchmarkSuite(self::$default_reporter);
		natcasesort($classList);
		foreach($classList as $className) {
			$suite->addBenchmark(new $className);
		}

		// Remove the error handler so that PHPUnit can add its own
		restore_error_handler();

		self::$default_reporter->writeHeader("SilverStripe Benchmark Runner");
		if (count($classList) > 1) {
			self::$default_reporter->writeInfo("All Benchmarks", "Running benchmarks: ",implode(", ", $classList));
		} elseif (count($classList) == 1) {
			self::$default_reporter->writeInfo(reset($classList), '');
		} else {
			// border case: no tests are available.
			self::$default_reporter->writeInfo('', '');
		}

		if(!Director::is_cli()) echo '<div class="trace">';
		$suite->runBenchmarks();

		$endTime = microtime(true);
		if(Director::is_cli()) echo "\n\nTotal time: " . round($endTime-$startTime,3) . " seconds\n";
		else echo "<p class=\"total-time\">Total time: " . round($endTime-$startTime,3) . " seconds</p>\n";
		
		if(!Director::is_cli()) echo '</div>';
		
		if(!Director::is_cli()) self::$default_reporter->writeFooter();
	}
}
