<?php
/*
 * This plugin is free to use
 * 
 * The following is amazing and was heavily referenced in the making:
 * Enhanced Account Switcher for MyBB 1.6 and 1.8
 * Copyright (c) 2012-2014 doylecc
 * http://mybbplugins.de.vu
 */

if(!defined("IN_MYBB"))
{
	die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

// Caching templates
global $templatelist, $templates, $db;
if (isset($templatelist))
{
	$templatelist .= ',';
}
$templatelist .= 'expmanage_submit,expmanage_cp_link,expmanage_fullview,expmanage_category,expmanage_thread';

function expmanager_info() {
	return array(
			"name"  => "EXP Manager",
			"description"=> "Allows different EXP Categories and for threads to be submitted to each for review.",
			"website"        => "https://github.com/megan-starr9/ExpManager/wiki",
			"author"        => "Megan Lyle",
			"authorsite"    => "http://megstarr.com",
			"version"        => "1.0",
			"guid"             => "",
			"compatibility" => "18*"
	);
}

// Load the install/admin functions in ACP.
if (defined("IN_ADMINCP"))
{
	require_once MYBB_ROOT."inc/plugins/expmanager/expmanage_install.php";
	require_once MYBB_ROOT."inc/plugins/expmanager/expmanage_admincp.php";
}
else  // Load all the frontend functions
{
	require_once MYBB_ROOT."inc/plugins/expmanager/expmanage_cp.php";
	require_once MYBB_ROOT."inc/plugins/expmanager/expmanage_functionality.php";
}

// Hook to add the javascript file (perhaps find better way)
$plugins->add_hook("global_end", "expmanager_scripts");

function expmanager_scripts() {
	global $footer;
	
	$footer .= '<script>
		<script src="inc/plugins/expmanager/expmanage_scripts.js" type="text/javascript">
			</script>';
}

?>