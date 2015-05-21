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
		$plugins->add_hook("admin_page_output_header", "expmanage_admin_scripts");
	}
}

// Add script that allows adding of new categories to the header
function expmanage_admin_scripts($args) {
	$args[this]->extra_header .= '<script src="../inc/plugins/expmanager/expmanage_scripts_admin.js" type="text/javascript"></script>';
}

/**
 * Add form to plugin settings
 */
function expmanager_custom_settings_editform() {
	global $db, $mybb, $form, $form_container;
	$cats = array();

	// Print category edit rows
	$query = $db->simple_select('expcategories');
	$form_container->output_row("", "", "<h2 style='display:inline;'>EXP Category Management</h2>");
	while($category = $query->fetch_assoc()) {
		$cats[] = $category;
	}
	$categoryform = '<table><tr><td>Delete?</td><td>Name</td><td>Rules</td><td title="Leave blank to
				disable auto-EXP (if award requires more than just simple thread count)"># of threads</td>
				<td>Exp Amount</td><td>Show Thread ids</td><td title="If no, will not allow duplicate
				submission in any other category with value set \'No\'">Allow Duplicates</td></tr>';
	foreach($cats as $cat) {
		$categoryform .= "<tr><td>".$form->generate_check_box("delete_cat[]", $cat['catid'], '', array('checked' => false))."</td>"
				."<td>".$form->generate_text_box("cat_name".$cat['catid'], $cat['cat_name'])."</td>"
				."<td>".$form->generate_text_area("cat_rules".$cat['catid'], $cat['cat_rules'])." </td>"
				."<td>".$form->generate_text_box("cat_threadamt".$cat['catid'], $cat['cat_threadamt'], array('style' => 'width:30px'))." </td>"
				."<td>".$form->generate_text_box("cat_expamt".$cat['catid'], $cat['cat_expamt'], array('style' => 'width:30px'))." </td>"
				."<td>".$form->generate_check_box("cat_showtids".$cat['catid'], 1,'', array('checked' => $cat['cat_showtids']))." </td>"
				."<td>".$form->generate_check_box("cat_allowduplicates".$cat['catid'], 1,'', array('checked' => $cat['cat_allowduplicates']))." </td></tr>";
	}
	$categoryform .= "</table><button type='button' id='create_category'>Create New Category</button>";
	$form_container->output_row("", "", $categoryform);
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
		// Delete first
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

			// Save non-deleted categories!
			$query = $db->simple_select('expcategories');
			while($category = $query->fetch_assoc()) {
				$cats[] = $category;
			}
			foreach($cats as $cat) {
				$cat_array = array(
					'cat_name' => $db->escape_string($mybb->input['cat_name'.$cat['catid']]),
					'cat_rules' => $db->escape_string($mybb->input['cat_rules'.$cat['catid']]),
					'cat_threadamt' => (int)$mybb->input['cat_threadamt'.$cat['catid']],
					'cat_expamt' => (int)$mybb->input['cat_expamt'.$cat['catid']],
					'cat_showtids' => (int)$mybb->input['cat_showtids'.$cat['catid']],
					'cat_allowduplicates' => (int)$mybb->input['cat_allowduplicates'.$cat['catid']]
				);
				$db->update_query("expcategories", $cat_array, 'catid="'.$cat['catid'].'"');
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
