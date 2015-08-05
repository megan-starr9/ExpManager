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
	eval("\$usercpmenu .= \"".$templates->get("expmanage_link_cp")."\";");
}

function expmanager_usercp()
{
	global $db, $mybb, $templates, $theme, $header, $footer, $headerinclude, $title, $usercpnav, $expmanage_fullview, $exp_submissions;

	if ($mybb->input['action'] == "expmanager")
	{
		$exp_submissions = '';
		$catselect = 'catid, cat_name, cat_expamt, cat_rules';
		$subselect = 'et.subid, et.sub_catid, et.sub_uid, t.tid, t.subject, et.sub_notes, et.sub_finalized, et.sub_approved, et.sub_otherposters';

		$categories = array();
		$query = $db->simple_select('expcategories', $catselect, 'EXISTS (SELECT sub_catid FROM '.TABLE_PREFIX.'expsubmissions WHERE sub_uid = '.$mybb->user['uid'].' AND sub_catid = catid)');
		while ($category = $query->fetch_assoc()) {
			$categories[] = $category;
		}

		$threads = array();
		$query2 = $db->simple_select('expsubmissions et INNER JOIN '.TABLE_PREFIX.'threads t ON t.tid = et.sub_tid', $subselect,  'et.sub_uid = '.$mybb->user['uid']);
		while ($thread = $query2->fetch_assoc()) {
			if(!is_array($threads[$thread['sub_catid']])) {
				$threads[$thread['sub_catid']] = array($thread);
			} else {
				$threads[$thread['sub_catid']][] = $thread;
			}
		}

		//Build category list
		foreach($categories as $category) {
			//Build threadlist for category
			foreach($threads[$category['catid']] as $thread) {
				$class = 'submitted';
				if($thread['sub_finalized']) {
					$class = 'exp_applied';
				} else if($thread['sub_approved']) {
					$class = 'approved';
				}
				$otherposters = json_decode($thread['sub_otherposters']);
				$submission_otherposters = '';
				foreach($otherposters as $key => $value) {
					if(strlen($submission_otherposters) > 0 ) {
						$submission_otherposters .= ', ';
					}
					$submission_otherposters .= $key.'('.$value.')';
				}
				eval("\$threadlist .= \"".$templates->get('expmanage_thread')."\";");
			}

			eval("\$exp_submissions .= \"".$templates->get('expmanage_category')."\";");
			// Reset threadlist for next go-round
			eval("\$threadlist = \"\";");
		}

		$getnotifications = $db->simple_select('expmodrequests', '*', 'uid='.$mybb->user['uid']);
		if($getnotifications->num_rows == 0) {
			eval("\$notify_button = \"".$templates->get('expmanage_buttons_notify')."\";");
		} else {
			$userid = $mybb->user['uid'];
			eval("\$notify_button = \"".$templates->get('expmanage_buttons_cancelnotify')."\";");
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
	eval("\$nav_announcements .= \"".$templates->get("expmanage_link_cp")."\";");
}

/**
 * Adds a Button to Modify User Page
 */
function expmanager_modusermenu() {
	global $mybb, $templates,$user,$requiredfields;

	$link = 'modcp.php?action=expmanager&uid='.$user['uid'];
	eval("\$requiredfields .= \"".$templates->get("expmanage_link_profile")."\";");
}

function expmanager_modcp() {
	global $db, $mybb, $templates, $theme, $header, $footer, $headerinclude, $title, $modcp_nav, $expmanage_fullview_mod, $exp_submissions;

	if ($mybb->input['action'] == "expmanager")
	{
		$exp_submissions = '';

		if(isset($mybb->input['uid'])) {
			// Manage more difficult EXP awards (those that aren't simply # of threads)
			$userid = (int)$mybb->input['uid'];
			exp_manage_user($userid);

			$getnotifications = $db->simple_select('expmodrequests', '*', 'uid='.$userid);
			if($getnotifications->num_rows != 0) {
				eval("\$cancel_notify_button = \"".$templates->get('expmanage_buttons_cancelnotify')."\";");
			}
			eval("\$expmanage_fullview_mod = \"".$templates->get('expmanage_fullview_usermod')."\";");

		} else {
			// General EXP Approval
			exp_manage_approval();
			eval("\$expmanage_fullview_mod = \"".$templates->get('expmanage_fullview_mod')."\";");
		}

		output_page($expmanage_fullview_mod);
	}
}

function exp_manage_user($userid) {
	global $mybb, $db, $templates, $exp_submissions;

	$catselect = 'catid, cat_name, cat_expamt, cat_rules, cat_threadamt';
	$subselect = 'et.subid, et.sub_catid, et.sub_uid, t.tid, t.subject, et.sub_notes, et.sub_finalized, et.sub_approved, et.sub_otherposters';

	$categories = array();
	$query = $db->simple_select('expcategories', $catselect, 'EXISTS(SELECT sub_catid FROM '.TABLE_PREFIX.'expsubmissions WHERE sub_catid = catid AND sub_uid = '.$userid.')');
	while($category = $query->fetch_assoc()) {
		$categories[] = $category;
	}

	$threads = array();
	$query2 = $db->simple_select('expsubmissions et INNER JOIN '.TABLE_PREFIX.'threads t ON t.tid = et.sub_tid', $subselect, 'et.sub_uid = '.$userid);
	while ($thread = $query2->fetch_assoc()) {
		if(!is_array($threads[$thread['sub_catid']])) {
			$threads[$thread['sub_catid']] = array($thread);
		} else {
			$threads[$thread['sub_catid']][] = $thread;
		}
	}

	//Build category list
	foreach($categories as $category) {
		//Build threadlist for category
		foreach($threads[$category['catid']] as $thread) {
			if($thread['sub_finalized']) {
				$class = 'exp_applied';
			} else if($thread['sub_approved']) {
				$class = 'approved';
			} else {
				$class = 'submitted';
			}
			// Workaround cuz MYBB hates me ><
			$submission_id = $thread['subid'];
			$otherposters = json_decode($thread['sub_otherposters']);
			$submission_otherposters = '';
			foreach($otherposters as $key => $value) {
				if(strlen($submission_otherposters) > 0 ) {
					$submission_otherposters .= ', ';
				}
				$submission_otherposters .= $key.'('.$value.')';
			}
			$action = "";
			$moderating = $category['cat_threadamt'] == 0 || !$mybb->settings['expmanager_autoaward'];
			if($thread['sub_approved'] && !$thread['sub_finalized'] && $moderating) {
				$action = '<input type=\'checkbox\' name=\'submit_cat'.$thread['sub_catid'].'[]\' value=\''.$submission_id.'\'></input>';
			}
			if($moderating) {
				eval("\$threadlist .= \"".$templates->get('expmanage_thread_usermod')."\";");
			} else {
				eval("\$threadlist .= \"".$templates->get('expmanage_thread')."\";");
			}
		}
		if($moderating) {
			eval("\$exp_submissions .= \"".$templates->get('expmanage_category_usermod')."\";");
		} else {
			eval("\$exp_submissions .= \"".$templates->get('expmanage_category')."\";");
		}

		eval("\$threadlist = \"\";");
	}
}

function exp_manage_approval() {
	global $mybb, $db, $templates, $exp_submissions;

	$catselect = 'catid, cat_name, cat_expamt, cat_rules';
	$subselect = 'et.subid, et.sub_catid, et.sub_uid, t.tid, t.subject, et.sub_notes, et.sub_finalized, et.sub_approved, et.sub_otherposters';

	$categories = array();
	$query = $db->simple_select('expcategories', $catselect, 'EXISTS(SELECT sub_catid FROM '.TABLE_PREFIX.'expsubmissions WHERE sub_catid = catid AND sub_approved = 0 AND sub_finalized = 0)');
	while ($category = $query->fetch_assoc()) {
		$categories[] = $category;
	}

	$threads = array();
	$query2 = $db->simple_select('expsubmissions et INNER JOIN '.TABLE_PREFIX.'threads t ON t.tid = et.sub_tid', $subselect, 'et.sub_approved = 0 AND et.sub_finalized = 0');
	while ($thread = $query2->fetch_assoc()) {
		if(!is_array($threads[$thread['sub_catid']])) {
			$threads[$thread['sub_catid']] = array($thread);
		} else {
			$threads[$thread['sub_catid']][] = $thread;
		}
	}

	//Build category list
	foreach($categories as $category) {
		//Build threadlist for category
		foreach($threads[$category['catid']] as $thread) {
			// Workaround cuz MYBB hates me ><
			$submission_id = $thread['subid'];
			$submission_user = get_user($thread['sub_uid']);
			$otherposters = json_decode($thread['sub_otherposters']);
			$submission_otherposters = '';
			foreach($otherposters as $key => $value) {
				if(strlen($submission_otherposters) > 0 ) {
					$submission_otherposters .= ', ';
				}
				$submission_otherposters .= $key.'('.$value.')';
			}
			eval("\$threadlist .= \"".$templates->get('expmanage_thread_mod')."\";");
		}

		eval("\$exp_submissions .= \"".$templates->get('expmanage_category_mod')."\";");
		eval("\$threadlist = \"\";");
	}
}
