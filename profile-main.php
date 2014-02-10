<?php

$profile = (php_sapi_name() != "cli" && !isset($_GET['flush']));

if($profile) require 'profile-start.php';
	

// Call silverstripe
require realpath(dirname(dirname(__FILE__)) . '/framework/main.php');

if($profile) require 'profile-end.php';