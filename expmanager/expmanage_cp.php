<?php
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />
		Please make sure IN_MYBB is defined.");
}

/**
 * USER CP
 */

// Add the hooks
$plugins->add_hook("usercp_menu", "expmanager_usercpmenu");
$plugins->add_hook("usercp_start", "expmanager_usercp");

/**
 * Adds a button to the usercp navigation.
 *
 *
*/
function expmanager_usercpmenu()
{
	global $db, $mybb, $templates, $usercpmenu;

	$link = 'usercp.php?action=expmanager';
	eval("\$usercpmenu .= \"".$templates->get("expmanage_cp_link")."\";");
}

function expmanager_usercp()
{
	global $db, $mybb, $templates, $theme, $header, $footer, $headerinclude, $title, $usercpnav, $expmanage_fullview, $exp_submissions;

	if ($mybb->input['action'] == "expmanager")
	{
		$exp_submissions = '';
		$categories = array();
		$query = $db->simple_select('expcategories', 'catid, cat_name, cat_expamt, cat_rules', 'EXISTS (SELECT sub_catid FROM '.TABLE_PREFIX.'expsubmissions WHERE sub_uid = '.$mybb->user['uid'].' AND sub_catid = catid)');
		while ($category = $query->fetch_assoc()) {
			$categories[] = $category;
		}

		//Build category list
		foreach($categories as $category) {

			$threads = array();
			$query2 = $db->query('SELECT et.sub_catid, et.sub_uid, t.tid, t.subject, et.sub_notes, et.sub_finalized, et.sub_approved FROM '.TABLE_PREFIX.'expsubmissions et INNER JOIN '.TABLE_PREFIX.'threads t ON t.tid = et.sub_tid WHERE et.sub_uid = '.$mybb->user['uid'].' AND et.sub_catid = '.$category['catid']);
			while ($thread = $query2->fetch_assoc()) {
				$threads[] = $thread;
			}
			//Build threadlist for category
			foreach($threads as $thread) {
				$class = 'submitted';
				if($thread['sub_finalized']) {
					$class = 'exp_applied';
				} else if($thread['sub_approved']) {
					$class = 'approved';
				}
				eval("\$threadlist .= \"".$templates->get('expmanage_thread')."\";");
			}

			eval("\$exp_submissions .= \"".$templates->get('expmanage_category')."\";");
			// Reset threadlist for next go-round
			eval("\$threadlist = \"\";");
		}

		eval("\$expmanage_fullview = \"".$templates->get('expmanage_fullview')."\";");

		return output_page($expmanage_fullview);
	}
}

/**
 * MOD CP
 */

// Add the hooks
$plugins->add_hook("modcp_nav", "expmanager_modcpmenu");
$plugins->add_hook("modcp_editprofile_end", "expmanager_modusermenu");
$plugins->add_hook("modcp_start", "expmanager_modcp");

/**
 * Adds a button to the modcp navigation.
 *
 *
 */
function expmanager_modcpmenu() {
	global $db, $mybb, $templates, $nav_announcements;

	$link = 'modcp.php?action=expmanager';
	eval("\$nav_announcements .= \"".$templates->get("expmanage_cp_link")."\";");
}

/**
 * Adds a Button to Modify User Page
 */
function expmanager_modusermenu() {
	global $mybb, $templates,$user,$requiredfields;

	$link = 'modcp.php?action=expmanager&uid='.$user['uid'];
	eval("\$requiredfields .= \"".$templates->get("expmanage_profile_link")."\";");
}

function expmanager_modcp() {
	global $db, $mybb, $templates, $theme, $header, $footer, $headerinclude, $title, $modcp_nav, $expmanage_fullview_mod, $exp_submissions;

	if ($mybb->input['action'] == "expmanager")
	{
		$exp_submissions = '';

		if(isset($mybb->input['uid'])) {
			// Manage more difficult EXP awards (those that aren't simply # of threads)
			exp_manage_user();

		} else {
			// General EXP Approval
			exp_manage_approval();

		}

		eval("\$expmanage_fullview_mod = \"".$templates->get('expmanage_fullview_mod')."\";");

		output_page($expmanage_fullview_mod);
	}
}

function exp_manage_user() {
	global $mybb, $db, $templates, $exp_submissions;

	$userid = (int)$mybb->input['uid'];

	$categories = array();
	$query = $db->simple_select('expcategories', 'catid, cat_name, cat_expamt, cat_rules', 'cat_threadamt = 0 AND EXISTS(SELECT sub_catid FROM '.TABLE_PREFIX.'expsubmissions WHERE sub_catid = catid AND sub_approved = 1 AND sub_finalized = 0 AND sub_uid = '.$userid.')');
	while($category = $query->fetch_assoc()) {
		$categories[] = $category;
	}

	//Build category list
	foreach($categories as $category) {

		$threads = array();
		$query2 = $db->query('SELECT et.subid, et.sub_catid, et.sub_uid, t.tid, t.subject, et.sub_notes, et.sub_finalized, et.sub_approved FROM '.TABLE_PREFIX.'expsubmissions et INNER JOIN '.TABLE_PREFIX.'threads t ON t.tid = et.sub_tid WHERE et.sub_uid = '.$userid.' AND et.sub_catid = '.$category['catid'].' AND et.sub_approved = 1 AND et.sub_finalized = 0');
		while ($thread = $query2->fetch_assoc()) {
			$threads[] = $thread;
		}
		//Build threadlist for category
		foreach($threads as $thread) {
			// Workaround cuz MYBB hates me ><
			$submission_id = $thread['subid'];
			eval("\$threadlist .= \"".$templates->get('expmanage_thread_usermod')."\";");
		}

		eval("\$exp_submissions .= \"".$templates->get('expmanage_category_usermod')."\";");
		eval("\$threadlist = \"\";");
	}
}

function exp_manage_approval() {
	global $mybb, $db, $templates, $exp_submissions;

	$categories = array();
	$query = $db->simple_select('expcategories', 'catid, cat_name, cat_expamt, cat_rules', 'EXISTS(SELECT sub_catid FROM '.TABLE_PREFIX.'expsubmissions WHERE sub_catid = catid AND sub_approved = 0)');
	while ($category = $query->fetch_assoc()) {
		$categories[] = $category;
	}

	//Build category list
	foreach($categories as $category) {

		$threads = array();
		$query2 = $db->query('SELECT et.subid, et.sub_catid, et.sub_uid, t.tid, t.subject, et.sub_notes, et.sub_finalized, et.sub_approved FROM '.TABLE_PREFIX.'expsubmissions et INNER JOIN '.TABLE_PREFIX.'threads t ON t.tid = et.sub_tid WHERE et.sub_catid = '.$category['catid'].' AND et.sub_approved = 0');
		while ($thread = $query2->fetch_assoc()) {
			$threads[] = $thread;
		}
		//Build threadlist for category
		foreach($threads as $thread) {
			// Workaround cuz MYBB hates me ><
			$submission_id = $thread['subid'];
			$submission_user = get_user($thread['sub_uid']);
			eval("\$threadlist .= \"".$templates->get('expmanage_thread_mod')."\";");
		}

		eval("\$exp_submissions .= \"".$templates->get('expmanage_category')."\";");
		eval("\$threadlist = \"\";");
	}
}
