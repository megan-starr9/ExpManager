<?php
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />
		Please make sure IN_MYBB is defined.");
}

/**
 * Add custom settings to plugin page
 */
// Add Hooks
$plugins->add_hook("admin_config_settings_change", "expmanager_custom_settings");
$plugins->add_hook("admin_config_settings_change_commit", "expmanager_custom_settings_commit");

function expmanager_custom_settings() {
	global $db, $mybb, $plugins;
	
	$query = $db->simple_select('settinggroups', 'gid', "name='expmanager'");
	$result = $query->fetch_assoc();
	
	if((int)$mybb->input['gid'] == (int)$result['gid']) {
		// We are in the right settings, now do some magic!
		$plugins->add_hook("admin_formcontainer_end", "expmanager_custom_settings_editform");
	}
}

/**
 * Add form to plugin settings
 */
function expmanager_custom_settings_editform() {
	global $db, $mybb, $form, $form_container;
	
	$form_container->output_row("", "", "<h2 style='display:inline;'>Add Exp Category</h2> <i>(Leave fields blank to opt out)</i>");
	$form_container->output_row("", "", "<b>Name*: </b>".$form->generate_text_box("cat_name", "")."<br>"
			."<b>Rules: </b>".$form->generate_text_area("cat_rules", "")."<br><br>"
			."<b># of Threads: </b>".$form->generate_text_box("cat_threadamt", 0)." <i>Leave blank to disable auto-EXP (if award requires more than just simple thread count)</i><br>"
			."<b>Exp Amount: </b>".$form->generate_text_box("cat_expamt", 0)."<br><br>"
			.$form->generate_check_box("cat_showtids", 1, "", array('checked' => True))."<b>Show Thread Ids</b><br><i style='margin-left:30px;'>When EXP is awarded, if Thread IDs will be included in description.</i><br>"
			.$form->generate_check_box("cat_allowduplicates",  1, "", array('checked' => True))." <b>Allow Duplicates</b><br><i style='margin-left:30px;'>If no, will not allow duplicate submission in any other category with value set 'No'</i><br>");
	$form_container->output_row("", "", "<h2 style='display:inline;'>Remove Exp Categories</h2> <i>(Check beside to remove)</i>");
	$delete_form = '';
	$categories = array();
	$query = $db->simple_select('expcategories', 'catid, cat_name');
	while ($category = $query->fetch_assoc()) {
		$categories[] = $category;
	}
	foreach ($categories as $category) {
		$delete_form .= $form->generate_check_box("delete_cat[]", $category['catid'], $category['cat_name'], array('checked' => false));
	}
	$form_container->output_row("","", $delete_form);
	
}

/**
 * Save custom settings on submit
 */
function expmanager_custom_settings_commit() {
	global $db, $mybb;

	$query = $db->simple_select('settinggroups', 'gid', "name='expmanager'");
	$result = $query->fetch_assoc();

	if((int)$mybb->input['gid'] == (int)$result['gid']) {
		// We are in the right settings, now do some magic!
		if(!empty($mybb->input['cat_name'])) {
			$category_array = array(
					'cat_name' => $db->escape_string($mybb->input['cat_name']),
					'cat_rules' => $db->escape_string($mybb->input['cat_rules']),
					'cat_threadamt' => (int)$mybb->input['cat_threadamt'],
					'cat_expamt' => (int)$mybb->input['cat_expamt'],
					'cat_showtids' => (int)$mybb->input['cat_showtids'],
					'cat_allowduplicates' => (int)$mybb->input['cat_allowduplicates']
			);
			$catid = $db->insert_query("expcategories", $category_array);
		}
		if(isset($mybb->input['delete_cat']) && is_array($mybb->input['delete_cat'])) {
			$delete_string = '';
			$delete_string2 = '';
			foreach($mybb->input['delete_cat'] as $category) {
				$to_delete = (int)$category;
				if(!empty($delete_string)) {
					$delete_string .= " OR ";
					$delete_string2 .= " OR ";
				}
				$delete_string .= "catid = ".$to_delete;
				$delete_string2 .= "sub_catid = ".$to_delete;				
			}
			if(!empty($delete_string)) {
				$db->delete_query('expcategories', $delete_string);
				$db->delete_query('expsubmissions', $delete_string2);
			}
		}
	}
}

/**
 * Adds a setting in forum options in ACP.
 *
 */
// Add Hooks
$plugins->add_hook("admin_forum_management_edit", "expmanager_forum_edit");
$plugins->add_hook("admin_forum_management_edit_commit", "expmanager_forum_commit");

function expmanager_forum_edit() {
	global $plugins;

	// Add new hook
	$plugins->add_hook("admin_formcontainer_end", "expmanager_forum_editform");
}

/**
 * Add additional input to form
 */
function expmanager_forum_editform() {
	global $mybb, $lang, $form, $form_container, $forum_data;

	// Create the input fields
	if ($form_container->_title == $lang->additional_forum_options)
	{
		$expmanage_forum_isallowed = array(
				$form->generate_check_box("expmanage_canSubmit", 1, "Users can submit threads for EXP", array("checked" => $forum_data['expmanage_canSubmit']))
		);
		$form_container->output_row("EXP Manager", "", "<div class=\"forum_settings_bit\">".implode("</div><div class=\"forum_settings_bit\">", $expmanage_forum_isallowed)."</div>");
	}
}

/**
 * Sets the forum options values in ACP on submit
 *
 */
function expmanager_forum_commit()
{
	global $mybb, $db, $fid;

	$update_array = array(
			'expmanage_canSubmit' => (int)$mybb->input['expmanage_canSubmit']
		);
	$db->update_query("forums", $update_array, "fid='$fid'");
}

?>