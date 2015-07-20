<?php

/*
 * Show Submit EXP button on threadview
 */
$plugins->add_hook("showthread_start", "show_submit_exp");

function show_submit_exp() {
	global $mybb, $db, $templates, $expmanage_submit, $thread, $footer;

	if(!isset($thread))
	{
		$thread = get_thread((int)$mybb->input['tid']);
	}

	$query = $db->simple_select("forums", "name,expmanage_canSubmit", "fid='".$thread['fid']."'");
	$parent_forum = $query->fetch_assoc();

	$allowedforums = explode(",", $mybb->settings['expmanager_boardscansubmit']);

	if(($parent_forum['expmanage_canSubmit'] == 1 || in_array($thread['fid'], $allowedforums))&& validate_thread($thread)) {
		eval("\$expmanage_submit = \"".$templates->get('expmanage_submit')."\";");
		$category_options = '';
		$categories = retrieve_available_categories($thread);
		foreach($categories as $category) {
			$category_options .= '<option value="'.$category['catid'].'">'.$category['cat_name'].'</option>';
		}
		eval("\$dialog = \"".$templates->get('expmanage_submit_dialog')."\";");
		$footer .= $dialog;
	}
}

/*
 * Ensure thread is valid for Exp
 * We only show the submit button if it is!
 */
function validate_thread($thread) {
	global $mybb, $db;

	// check if total number of posts is acceptable
	$countquery = $db->simple_select("posts", "pid, message", "tid = ". $thread['tid'] ." AND visible = 1");
	if($db->num_rows($countquery) < (int)$mybb->settings['expmanager_postnum_total']) {
		return false;
	}

	$query = $db->simple_select("posts", "pid, message", "tid = ". $thread['tid'] ." AND username = '". $mybb->user['username'] ."' AND visible = 1");
	$posts = array();
	while ($post = $query->fetch_assoc()) {
		$posts[] = $post;
	}

	// Check if user even posted in thread correct amount
	$postnum_req = (int)$mybb->settings['expmanager_postnumrequired'];
	if(($postnum_req != 0 && count($posts) < $postnum_req) || count($posts) == 0) {
		return false;
	}

	// Check if it is valid in any exp categories.  if not, no point!
	if(count(retrieve_available_categories($thread)) == 0) {
		return false;
	}

	//Check word or character count
	$charcount_req = (int)$mybb->settings['expmanager_charcountrequired'];
	$wordcount_req = (int)$mybb->settings['expmanager_wordcountrequired'];
	$valid_posts = 0;
	foreach($posts as $post) {
		if($charcount_req != 0) {
			if(strlen($post['message']) >= $charcount_req) {
				$valid_posts++;
			}
		} else if($wordcount_req != 0) {
			$wordarray = explode(' ', $post['message']);
			if(count($wordarray) >= $wordcount_req) {
				$valid_posts++;
			}
		} else {
			return true; // check doesn't matter if neither is set
		}
	}
	return $valid_posts >= $postnum_req;
}

/*
* Get count of valid posts per character
*/
function get_other_char_posts($threadid) {
	global $mybb, $db;

	$query = $db->simple_select("posts", "pid, message,username", "tid = ". $threadid ." AND username <> '". $mybb->user['username'] ."' AND visible = 1");
	$posts = array();
	while ($post = $query->fetch_assoc()) {
		$posts[] = $post;
	}

	$charcount_req = (int)$mybb->settings['expmanager_charcountrequired'];
	$wordcount_req = (int)$mybb->settings['expmanager_wordcountrequired'];
	$charposts = array();
	// Get count of valid posts by each character, checking word/character counts
	foreach($posts as $post) {
		if(!isset($charposts[$post['username']])) {
			$charposts[$post['username']] = 0;
		}
		if($charcount_req != 0) {
			if(strlen($post['message']) >= $charcount_req) {
				$charposts[$post['username']] += 1;
			}
		} else if($wordcount_req != 0) {
			$wordarray = explode(' ', $post['message']);
			if(count($wordarray) >= $wordcount_req) {
				$charposts[$post['username']] += 1;
			}
		} else {
			$charposts[$post['username']] += 1;
		}
	}
	return $charposts;
}

/**
 * Get categories it can be submitted to (no duplicating submissions)
 */
function retrieve_available_categories($thread) {
	global $mybb, $db;
	$cats_already_in = array();
	$query = $db->simple_select("expsubmissions", "subid, sub_tid, sub_catid", "sub_tid='".$thread['tid']."'");
	while($submission = $query->fetch_assoc()) {
		$cats_already_in[] = $submission['sub_catid'];
	}

	$where = "";
	// If there are categories the thread already belongs to...
	if(count($cats_already_in) > 0) {
		$query2 = $db->simple_select("expcategories", "catid", "catid IN (".implode(',',$cats_already_in).") AND cat_allowduplicates = 0");
		$no_dup = $db->num_rows > 0;

		// No duplicates in the same category
		$where = "catid NOT IN (".implode(',',$cats_already_in).")";
		if($no_dup) {
			// Block duplicates between categories that don't allow it
			$where .= " AND cat_allowduplicates = 1";
		}
	}

	$cats_avail = array();
	$query3 = $db->simple_select("expcategories", "catid, cat_name", $where);
	while($category = $query3->fetch_assoc()) {
		$cats_avail[] = $category;
	}
	return $cats_avail;
}

/**
* Figure alerts!
*/
$plugins->add_hook('global_intermediate', 'add_alert');

function add_alert() {
	global $db,$mybb,$templates,$expmanage_requests;

	$allowedgroups = array(3,4,6);
	if($mybb->settings['expmanager_usenotifications'] && isset($mybb->user) && (in_array($mybb->user['usergroup'], $allowedgroups)
					|| count(array_intersect($allowedgroups, explode(',', $mybb->user['additionalgroups']))) > 0)) {
		$expmanage_alert_text = "";
			// Get any undealt with submissions
		$need_accept = $db->simple_select("expsubmissions", "subid", "sub_approved = '0'");
		if($db->num_rows($need_accept) > 0) {
			$expmanage_alert_text = "There are EXP submissions that need moderating.  Go to <a href='modcp.php?action=expmanager'>EXP Management</a>.";
		}

		// Now get any user requests for moderation
		$requested_mod = $db->simple_select("expmodrequests r INNER JOIN ".TABLE_PREFIX."users u ON r.uid = u.uid", "u.uid, u.username");
		while($req = $requested_mod->fetch_assoc()) {
			if(strlen($expmanage_alert_text) > 0) {
				$expmanage_alert_text .= "<br>";
			}
			$expmanage_alert_text .= "User <b>".$req['username']."</b> has requested EXP moderation.  Go to <a href='modcp.php?action=expmanager&uid=".$req['uid']."'>their EXP Management</a>.";
		}
		if(strlen($expmanage_alert_text) > 0) {
			eval("\$expmanage_requests = \"".$templates->get('expmanage_alert')."\";");
		}
	}
}

/**
 * Handle XMLHTTP requests
 *
 */
$plugins->add_hook('xmlhttp', 'handle_ajax_request');

function handle_ajax_request() {
	global $mybb;
	if($mybb->input['action'] == 'submitexp') {
		// User is submitting thread for EXP consideration
		thread_submission();
	} else if($mybb->input['action'] == 'approveexp') {
		// Moderator is approving thread as valid
		thread_approval();
	} else if($mybb->input['action'] == 'finalizeexp') {
		// Moderator is marking exp as rewarded
		thread_finalize();
	} else if($mybb->input['action'] == 'denyexp') {
		// Moderator is marking exp as rewarded
		thread_deny();
	} else if($mybb->input['action'] == 'createcategory') {
		create_category();
	} else if($mybb->input['action'] == 'requestexpmod') {
		request_moderation();
	}  else if($mybb->input['action'] == 'requestexpmod_cancel') {
		cancel_moderation();
	}
}

/**
 * User submitted thread
 */
function thread_submission() {
	global $mybb, $db;

	if(isset($mybb->input['sub_tid']) && isset($mybb->input['sub_catid'])) {
		//if(valid_submission($mybb->input['sub_tid'], $mybb->input['sub_catid'])) { // Don't really need this, but in case
			$usergroup = ($mybb->user['displaygroup'] == 0) ? (int)$mybb->user['usergroup'] : (int)$mybb->user['displaygroup'];
			$submission_info = array(
					'sub_tid' => (int)$mybb->input['sub_tid'],
					'sub_catid' => (int)$mybb->input['sub_catid'],
					'sub_uid' => (int)$mybb->user['uid'],
					'sub_group' => $usergroup,
					'sub_notes' => $db->escape_string($mybb->input['sub_notes']),
					'sub_otherposters' => json_encode(get_other_char_posts((int)$mybb->input['sub_tid']))
			);
			$subid = $db->insert_query('expsubmissions', $submission_info);
	//	}
	}

}

/**
 *
 * NOT CURRENTLY USED!!!  Was going to determine if above submission was valid,
 * but does not work correctly
 */
function valid_submission($threadid, $catid) {
	global $mybb, $db, $thread;
	if(!isset($thread)) {
		$thread = get_thread($threadid);
	}

	foreach(retrieve_available_categories($thread) as $cat) {
		if((int)$cat['catid'] == $catid) {
			return validate_thread($thread);
		}
	}

	return false;
}

/**
 * Moderator approved EXP submission
 */
function thread_approval() {
	global $mybb, $db;
	if(isset($mybb->input['subid'])) {
		$db->update_query('expsubmissions', array('sub_approved' => 1), 'subid = '.(int)$mybb->input['subid']);

		// Get Category, see if it has a thread amount
		$query1 = $db->query('SELECT c.catid, c.cat_name, c.cat_threadamt, c.cat_expamt, c.cat_showtids, s.sub_uid FROM '.TABLE_PREFIX.'expcategories c INNER JOIN '.TABLE_PREFIX.'expsubmissions s ON c.catid = s.sub_catid WHERE s.subid = '.(int)$mybb->input['subid']);
		$category = $query1->fetch_assoc();

		if($category['cat_threadamt'] > 0) {
			add_reputation($category);
		}
	}
}

/*
 * Determine whether user has threads for reputation.  if so, grant it!
 */
function add_reputation($category) {
	global $mybb, $db;
	// Now we need to see if the user has enough submissions
	$query2 = $db->simple_select('expsubmissions', 'subid, sub_tid', 'sub_approved = 1 AND sub_finalized = 0 AND sub_uid = '.$category['sub_uid'].' AND sub_catid = '.$category['catid']);

	if($query2->num_rows >= $category['cat_threadamt']) {
		// If it does, collect the ids for awarding EXP
		$submission_tids = array();
		$finalized_subs = '';
		$i = 0;
		while($submission = $query2->fetch_assoc()) {
			if($i >= $category['cat_threadamt']) {
				break;
			}
			$submission_tids[] = $submission['sub_tid'];
			if(!empty($finalized_subs)) {
				$finalized_subs .= " OR ";
			}
			$finalized_subs .= "subid = ".$submission['subid'];
			$i++;
		}
		$ids = '';
		if($category['cat_showtids']) {
			$ids = " (IDs: ".implode(', ', $submission_tids).")";
		}
		// Award EXP
		$exp_arr = array(
				uid => $category['sub_uid'],
				adduid => $mybb->user['uid'],
				reputation => $category['cat_expamt'],
				dateline => TIME_NOW,
				comments => $category['cat_name'].$ids
		);
		$rid = $db->insert_query('reputation', $exp_arr);

		// Update Submissions to finalized if reputation goes through
		if($rid) {
			$db->update_query('expsubmissions', array('sub_finalized' => 1), $finalized_subs);
		}
	}
}

/**
 * Moderator wishes to give EXP for more complicated achievements
 */
function thread_finalize() {
	global $mybb, $db;
	if(isset($mybb->input['sub_catid']) && isset($mybb->input['subids'])) {
		$query = $db->simple_select('expcategories', 'catid, cat_name, cat_expamt, cat_showtids', 'catid = '.(int)$mybb->input['sub_catid']);
		$category = $query->fetch_assoc();

		$subid_string = "(".implode(",",$mybb->input['subids']).")";

		$ids = '';
		if($category['cat_showtids'] == 1) {
			$ids .= ' (IDs: ';
			$query2 = $db->simple_select('expsubmissions', 'sub_tid', 'subid IN '.$subid_string);
			while($tid = $query2->fetch_assoc()) {
				$ids .= $tid['sub_tid'].', ';
			}
			$ids = substr($ids, 0, strlen($ids)-2);
			$ids .= ')';
		}

		// Award EXP
		$exp_arr = array(
				uid => (int)$mybb->input['uid'],
				adduid => (int)$mybb->user['uid'],
				reputation => $category['cat_expamt'],
				dateline => TIME_NOW,
				comments => $category['cat_name'].$ids
		);
		$rid = $db->insert_query('reputation', $exp_arr);

		// Update Submissions to finalized if reputation goes through
		if($rid) {
			$db->update_query('expsubmissions', array('sub_finalized' => 1), 'subid IN '.$subid_string);
			// remove any requests user has sent
			$db->delete_query('expmodrequests', 'uid = '.(int)$mybb->input['uid']);
		}

	}
}

/**
 * Thread(s) are being denied for EXP
 */
function thread_deny() {
	global $mybb, $db;

	if(isset($mybb->input['subid'])) {
		// Single thread deny
		$db->delete_query('expsubmissions', 'subid = '.(int)$mybb->input['subid']);

	} else if(isset($mybb->input['subids'])) {
		// Multi thread deny
		$subid_string = "(".implode(",",$mybb->input['subids']).")";
		$db->delete_query('expsubmissions', 'subid IN '.$subid_string);
		// remove any requests user has sent
		$db->delete_query('expmodrequests', 'uid = '.(int)$mybb->input['uid']);
	}
}

/**
 * Request moderation / cancel a moderation request on user's EXP
 */
function request_moderation() {
	global $mybb, $db;

	if($mybb->settings['expmanager_usenotifications']) {
		if(isset($mybb->input['userid'])) {
			// Create a notification for moderators!
			$db->insert_query('expmodrequests', array('uid' => (int)$mybb->input['userid']));
		}
	}
}

function cancel_moderation() {
	global $mybb, $db;

	if(isset($mybb->input['userid'])) {
		// Create a notification for moderators!
		$db->delete_query('expmodrequests', 'uid = '.(int)$mybb->input['userid']);
	}
}

// Create stub category!
function create_category() {
	global $db;
	$newcat = array(
		'cat_name' => 'Name'
	);
	$db->insert_query('expcategories', $newcat);
}
