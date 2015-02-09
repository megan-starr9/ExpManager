<?php
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
} 

/**
 * Install Plugin
 */
function expmanager_install() {
	global $db;
	
	// Make DB Changes
	// Avoid database errors
	if ($db->field_exists("expmanage_canSubmit", "forums"))
	{
		$db->write_query("ALTER TABLE `".TABLE_PREFIX."forums` DROP COLUMN `expmanage_canSubmit`");
	}
	if ($db->table_exists("expsubmissions"))
	{
		$db->write_query("DROP TABLE `".TABLE_PREFIX."expsubmissions`");
	}
	if ($db->table_exists("expcategories"))
	{
		$db->write_query("DROP TABLE `".TABLE_PREFIX."expcategories`");
	}
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."forums` ADD COLUMN `expmanage_canSubmit` INT(1) NOT NULL DEFAULT '0'");
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."expsubmissions` (subid int(11) NOT NULL AUTO_INCREMENT, sub_catid int(11), sub_tid int(11), sub_notes varchar(300), sub_uid int(11), sub_approved int(1) NOT NULL DEFAULT 0, sub_finalized int(1) NOT NULL DEFAULT 0, sub_time timestamp default CURRENT_TIMESTAMP, PRIMARY KEY(subid))");
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."expcategories` (catid int(11) NOT NULL AUTO_INCREMENT, cat_name varchar(200), cat_rules text, cat_threadamt int(11), cat_expamt int(11), cat_showtids int(1), cat_allowduplicates int(1), PRIMARY KEY(catid))");

	// Create Settings
	$expmanager_group = array(
			'gid'    => 'NULL',
			'name'  => 'expmanager',
			'title'      => 'EXP Manager',
			'description'    => 'Settings For EXP Manager',
			'disporder'    => "1",
			'isdefault'  => "0",
	);
	
	$db->insert_query('settinggroups', $expmanager_group);
	$gid = $db->insert_id();
	
	$expmanager_settings[0] = array(
					'sid'            => 'NULL',
					'name'        => 'expmanager_postnumrequired',
					'title'            => 'Number of Posts Required',
					'description'    => 'Number of posts required to have been made by submitting user for thread to count (0 to disable).',
					'optionscode'    => 'text',
					'value'        => '0',
					'disporder'        => 1,
					'gid'            => intval($gid),
			);
	$expmanager_settings[1] = array(
					'sid'            => 'NULL',
					'name'        => 'expmanager_wordcountrequired',
					'title'            => 'Wordcount Required',
					'description'    => 'Number of words required in a post to have it count towards above count (0 to disable).',
					'optionscode'    => 'text',
					'value'        => '0',
					'disporder'        => 2,
					'gid'            => intval($gid),
			);
	
	foreach($expmanager_settings as $setting) {
		$db->insert_query('settings', $setting);
	}
	rebuild_settings();
	
	// Create any Templates
	// Add the new templates
	$expmanager_templates[0] = array(
			"title" 	=> "expmanage_submit",
			"template"	=> $db->escape_string('<a href="javascript:void(0)"  class="button submitexp"><span><i style="font-size: 14px;" class="fa fa-chevron-up  fa-fw"></i> Submit for EXP</span></a>'),
			"sid"		=> -1,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
		);
	$expmanager_templates[1] = array(
			"title" 	=> "expmanage_cp_link",
			"template"	=> $db->escape_string('<tbody><tr><td class="trow1 smalltext"><a href="{$link}" class="usercp_nav_item modcp_nav_item"><i style="font-size: 14px;" class="fa fa-check-circle-o  fa-fw"></i> View EXP Threads</a></td></tr></tbody>'),
			"sid"		=> -1,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
	$expmanager_templates[2] = array(
			"title" 	=> "expmanage_fullview",
			"template"	=> $db->escape_string('<html><head>
							  <title>{$mybb->settings[\'bbname\']} - EXP Manager</title>{$headerinclude}
							  <style>
							    .submitted {
							      /* Thread not approved for EXP */
							    }
							    .approved {
							      /* Thread approved for EXP but points aren\'t yet awarded */
							      color: #00ff00;
							    }
							    .exp_applied {
							      /* Thread approved for EXP and points have been awarded for it */
							      text-decoration: line-through;
							    }
							  </style>
							  </head>
						<body>
						{$header}
						<table width="100%" border="0" align="center">
							<tr>
							{$usercpnav}
								<td valign="top">
									<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
										<tr><td class="thead" colspan="2"><strong>EXP Submissions</strong></td></tr>
										<tr>
											<td class="trow1" valign="top">
													{$exp_submissions}
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						{$footer}
						</body>
						</html>'),
			"sid"		=> -1,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
	$expmanager_templates[3] = array(
			"title" 	=> "expmanage_category",
			"template"	=> $db->escape_string('<h1>{$category[\'cat_name\']} (+{$category[\'cat_expamt\']} EXP)</h1>
						{$category[\'cat_rules\']}
						<ol>
						{$threadlist}
						</ol>'),
			"sid"		=> -1,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
	$expmanager_templates[4] = array(
			"title" 	=> "expmanage_thread",
			"template"	=> $db->escape_string('<li>
					<a target=\'_blank\' href=\'showthread.php?tid={$thread[\'tid\']}\'>
					<span class="{$class}">{$thread[\'subject\']}</span>
					</a> &mdash; <i>({$thread[\'sub_notes\']})</i></li>'),
			"sid"		=> -1,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
	$expmanager_templates[5] = array(
			"title" 	=> "expmanage_fullview_mod",
			"template"	=> $db->escape_string('<html><head>
							  <title>{$mybb->settings[\'bbname\']} - EXP Manager</title>{$headerinclude}
							  </head>
						<body>
						{$header}
						<table width="100%" border="0" align="center">
							<tr>
							{$modcp_nav}
								<td valign="top">
									<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
										<tr><td class="thead" colspan="2"><strong>EXP Submissions</strong></td></tr>
										<tr>
											<td class="trow1" valign="top">
													{$exp_submissions}
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						{$footer}
						</body>
						</html>'),
			"sid"		=> -1,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
	$expmanager_templates[6] = array(
			"title" 	=> "expmanage_thread_mod",
			"template"	=> $db->escape_string('<li><span>
							<a target=\'_blank\' href=\'showthread.php?tid={$thread[\'tid\']}\'>{$thread[\'subject\']}</a>
 								 </span> &mdash; <i>({$thread[\'sub_notes\']})</i>
  								 &mdash; <a target=\'_blank\' href=\'member.php?action=profile&uid={$submission_user[\'uid\']}\'>{$submission_user[\'username\']}</a> 
								&mdash; <a href=\'javascript:void(0)\' id=\'{$submission_id}\' class=\'expapprove_button\'>Approve Request</a> | <a href=\'javascript:void(0)\' id=\'{$submission_id}\' class=\'expapprove_button_deny\'>Deny Request</a></li>'),
			"sid"		=> -1,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
	$expmanager_templates[7] = array(
			"title" 	=> "expmanage_thread_usermod",
			"template"	=> $db->escape_string('<li><span>
  					<input type=\'checkbox\' name=\'submit_cat{$thread[\'sub_catid\']}[]\' value=\'{$submission_id}\'></input><a target=\'_blank\' href=\'showthread.php?tid={$thread[\'tid\']}\'>{$thread[\'subject\']}</a>
  					</span> &mdash; <i>{$thread[\'sub_notes\']}</i></li>'),
			"sid"		=> -1,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
	$expmanager_templates[8] = array(
			"title" 	=> "expmanage_category_usermod",
			"template"	=> $db->escape_string('<h1>{$category[\'cat_name\']} (+{$category[\'cat_expamt\']} EXP)</h1>
						{$category[\'cat_rules\']}
						<ol>
						{$threadlist}
						</ol>
						<a href=\'javascript:void(0)\' id=\'{$category[\'catid\']}\' class=\'expfinalize_button button\'>Award EXP for Threads</a> 
						<a href=\'javascript:void(0)\' id=\'{$category[\'catid\']}\' class=\'expfinalize_button_deny button\'>Deny EXP for Threads</a>'),
			"sid"		=> -1,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
	$expmanager_templates[9] = array(
			"title" 	=> "expmanage_submit_dialog",
			"template"	=> $db->escape_string('<div id="expdialog" class="modal current">
					<form>
					<table style="padding: 0[px;" class="tborder" border="0" cellpadding="5" cellspacing="0" width="100%">
						<tbody><tr>
						<td class="thead" colspan="2"><strong>Submit Thread for Exp</strong></td>
						</tr>
						<tr>
						<td class="trow1" style="text-shadow: 1px 1px 0px #000;" width="25%"><strong>EXP Category:</strong></td>
						<td class="trow1"><select name="sub_catid">{$category_options}</select></td>
						</tr>
						<tr>
						<td class="trow2" style="text-shadow: 1px 1px 0px #000;"><strong>Notes:</strong></td>
						<td class="trow2"><textarea name="sub_notes"></textarea></td>
						</tr>
						<tr>
						<td class="trow2" colspan="2">
						<input type="hidden" name="sub_tid" value="{$thread[\'tid\']}">
						<div align="center">
							<a href="javascript:void(0);" class="button expsubmit_button">Submit</a>
						</div></td>
						</tr>
						</tbody></table>
					</form>
				</div>'),
			"sid"		=> -1,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
	$expmanager_templates[10] = array(
			"title" 	=> "expmanage_profile_link",
			"template"	=> $db->escape_string('<tbody><tr><td class="trow1 smalltext"><a href="{$link}" class="usercp_nav_item modcp_nav_item"><i style="font-size: 14px;" class="fa fa-check-circle-o  fa-fw"></i> Manage User\'s EXP Threads</a></td></tr></tbody>'),
			"sid"		=> -1,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
	foreach ($expmanager_templates as $row) {
		$db->insert_query('templates', $row);
	}
}

/**
 * Determine if plugin is installed based on changes made on install
 * @return boolean
 */
function expmanager_is_installed()
{
	global $db;

	if ($db->field_exists("expmanage_canSubmit", "forums") && $db->table_exists('expsubmissions') && $db->table_exists('expcategories')) {
		return true;
	}
	else
	{
		return false;
	}
}

/**
 * Activate Plugin
 */
function expmanager_activate() {
	// Apply any Template Edits
	//First undo
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets('showthread', '#{\$newreply}{\$expmanage_submit}#', '{\$newreply}');
	// Then apply
	find_replace_templatesets('showthread', '#{\$newreply}#', '{\$newreply}{\$expmanage_submit}');
}

/**
 * Deactivate Plugin
 */
function myfirstplugin_deactivate()
{
	// Revert any Template Edits
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets('showthread', '#{\$newreply}{\$expmanage_submit}#', '{\$newreply}');
}

/**
 * Uninstall Plugin
 */
function expmanager_uninstall()
{
	global $db;
	
	// Ensure template edits are reverted
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets('showthread', '#{\$newreply}{\$expmanage_submit}#', '{\$newreply}');

	// Delete any templates
	$db->delete_query("templates", "`title` = 'expmanage_submit'");
	$db->delete_query("templates", "`title` = 'expmanage_cp_link'");
	$db->delete_query("templates", "`title` = 'expmanage_fullview'");
	$db->delete_query("templates", "`title` = 'expmanage_fullview_mod'");
	$db->delete_query("templates", "`title` = 'expmanage_category'");
	$db->delete_query("templates", "`title` = 'expmanage_category_usermod'");
	$db->delete_query("templates", "`title` = 'expmanage_thread'");
	$db->delete_query("templates", "`title` = 'expmanage_thread_mod'");
	$db->delete_query("templates", "`title` = 'expmanage_thread_usermod'");
	$db->delete_query("templates", "`title` = 'expmanage_submit_dialog'");
	$db->delete_query("templates", "`title` = 'expmanage_profile_link'");
	
	// Delete any table columns
	if ($db->field_exists("expmanage_canSubmit", "forums"))
	{
		$db->write_query("ALTER TABLE `".TABLE_PREFIX."forums` DROP COLUMN `expmanage_canSubmit`");
	}
	if ($db->table_exists("expsubmissions"))
	{
		$db->write_query("DROP TABLE `".TABLE_PREFIX."expsubmissions`");
	}
	if ($db->table_exists("expcategories"))
	{
		$db->write_query("DROP TABLE `".TABLE_PREFIX."expcategories`");
	}
	
	// Delete settings
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN ('expmanager_postnumrequired')");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN ('expmanager_wordcountrequired')");
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='expmanager'");
	rebuild_settings();
}

?>