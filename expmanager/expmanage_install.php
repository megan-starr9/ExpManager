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
  if ($db->table_exists("expmodrequests"))
	{
		$db->write_query("DROP TABLE `".TABLE_PREFIX."expmodrequests`");
	}
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."forums` ADD COLUMN `expmanage_canSubmit` INT(1) NOT NULL DEFAULT '0'");
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."expsubmissions` (subid int(11) NOT NULL AUTO_INCREMENT, sub_catid int(11), sub_tid int(11), sub_notes varchar(300), sub_otherposters text, sub_uid int(11), sub_group int(11), sub_approved int(1) NOT NULL DEFAULT 0, sub_finalized int(1) NOT NULL DEFAULT 0, sub_time timestamp default CURRENT_TIMESTAMP, PRIMARY KEY(subid))");
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."expcategories` (catid int(11) NOT NULL AUTO_INCREMENT, cat_name varchar(200), cat_rules text, cat_threadamt int(11), cat_expamt int(11), cat_showtids int(1) NOT NULL DEFAULT 1, cat_allowduplicates int(1) NOT NULL DEFAULT 1, PRIMARY KEY(catid))");
  $db->write_query("CREATE TABLE `".TABLE_PREFIX."expmodrequests` (reqid int(11) NOT NULL AUTO_INCREMENT, uid int(11) NOT NULL, PRIMARY KEY(reqid))");

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
					'description'    => 'Number of posts required to have been made by submitting user for thread to count (0 to disable) (length is checked).',
					'optionscode'    => 'text',
					'value'        => '0',
					'disporder'        => 1,
					'gid'            => intval($gid),
			);
      $expmanager_settings[1] = array(
    					'sid'            => 'NULL',
    					'name'        => 'expmanager_postnum_total',
    					'title'            => 'Total Number of Posts Required in Thread',
    					'description'    => 'Number of posts required in a finished thread overall (0 to disable) (length isn\'\'t checked on all).',
    					'optionscode'    => 'text',
    					'value'        => '0',
    					'disporder'        => 2,
    					'gid'            => intval($gid),
    			);
      $expmanager_settings[2] = array(
    					'sid'            => 'NULL',
    					'name'        => 'expmanager_charcountrequired',
    					'title'            => 'Character count Required',
    					'description'    => 'Number of characters required in a post to have it count towards above count (0 to disable/use words).',
    					'optionscode'    => 'text',
    					'value'        => '0',
    					'disporder'        => 3,
    					'gid'            => intval($gid),
    			);
	$expmanager_settings[3] = array(
					'sid'            => 'NULL',
					'name'        => 'expmanager_wordcountrequired',
					'title'            => 'Wordcount Required',
					'description'    => 'Number of words required in a post to have it count towards above count (0 to disable, only used if char count is 0).',
					'optionscode'    => 'text',
					'value'        => '0',
					'disporder'        => 4,
					'gid'            => intval($gid),
			);
      $expmanager_settings[4] = array(
    					'sid'            => 'NULL',
    					'name'        => 'expmanager_boardscansubmit',
    					'title'            => 'Included Boards',
    					'description'    => 'Ids of boards where EXP can be submitted, overrides forum setting of false (comma-separated).',
    					'optionscode'    => 'text',
    					'value'        => '0',
    					'disporder'        => 5,
    					'gid'            => intval($gid),
    			);
        $expmanager_settings[5] = array(
      				'sid'            => 'NULL',
      				'name'        => 'expmanager_usenotifications',
      				'title'            => 'Show Notifications',
      				'description'    => 'Whether to show mods/admins notifications when submissions need managing.',
      				'optionscode'    => 'yesno',
      				'value'        => 1,
      				'disporder'        => 6,
      				'gid'            => intval($gid),
      		);
        $expmanager_settings[6] = array(
          'sid'            => 'NULL',
          'name'        => 'expmanager_notifygroups',
          'title'            => 'Groups to Notify',
          'description'    => 'Groups to display exp notifications to (if none are provided, will be Mods and Admins)',
          'optionscode'    => 'text',
          'value'        => '',
          'disporder'        => 7,
          'gid'            => intval($gid),
        );
        $expmanager_settings[7] = array(
          'sid'            => 'NULL',
          'name'        => 'expmanager_autoaward',
          'title'            => 'Auto-award EXP?',
          'description'    => 'Master switch for auto-awarding.  If set to no, all reputation will be managed manually.',
          'optionscode'    => 'yesno',
          'value'        => '',
          'disporder'        => 8,
          'gid'            => intval($gid),
        );

	foreach($expmanager_settings as $setting) {
		$db->insert_query('settings', $setting);
	}
	rebuild_settings();

	// Create any Templates
  //First add the group
  $templategroup = array(
    'prefix' => 'expmanage',
    'title'  => 'EXP Manager',
    'isdefault' => 1
  );
  $db->insert_query("templategroups", $templategroup);

	// Add the new templates
	$expmanager_templates[0] = array(
			"title" 	=> "expmanage_buttons_submit",
			"template"	=> $db->escape_string('<a href="javascript:void(0)"  class="button submitexp"><span><i style="font-size: 14px;" class="fa fa-chevron-up  fa-fw"></i> Submit for EXP</span></a>
          <a href="javascript:void(0)"  class="button markexpawarded"><span><i style="font-size: 14px;" class="fa fa-chevron-up  fa-fw"></i> Mark as Awarded</span></a>'),
			"sid"		=> -2,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
		);
	$expmanager_templates[1] = array(
			"title" 	=> "expmanage_link_cp",
			"template"	=> $db->escape_string('<tbody><tr><td class="trow1 smalltext"><a href="{$link}" class="usercp_nav_item modcp_nav_item"><i style="font-size: 14px;" class="fa fa-check-circle-o  fa-fw"></i> View EXP Threads</a></td></tr></tbody>'),
			"sid"		=> -2,
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
									background-color:#F0F0B2;
							    }
							    .approved {
							      /* Thread approved for EXP but points aren\'t yet awarded */
							      background-color:#A0CAAF;
							    }
							    .exp_applied {
							      /* Thread approved for EXP and points have been awarded for it */
							      background-color:#EAEAEA;
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
                          <br><br>
  											  {$notify_button}
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						{$footer}
						</body>
						</html>'),
			"sid"		=> -2,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
	$expmanager_templates[3] = array(
			"title" 	=> "expmanage_category",
			"template"	=> $db->escape_string('<h1>{$category[\'cat_name\']} (+{$category[\'cat_expamt\']} EXP)</h1>
<p style=\'margin-top:-10px;margin-bottom:10px;margin-left:50px;\'>{$category[\'cat_rules\']}</p>
						<table style=\'width:90%;margin:auto;text-align:center\'>
							<tr class=\'thead\' style=\'font-weight:bold\'>
								<td width=40%>Thread</td>
								<td width=40%>Submission Notes</td>
								<td width=30%>Other Participants</td>
							</tr>
						{$threadlist}
						</table>'),
			"sid"		=> -2,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
	$expmanager_templates[4] = array(
			"title" 	=> "expmanage_thread",
			"template"	=> $db->escape_string('<tr class=\'{$class}\'>
	<td><a target=\'_blank\' href=\'showthread.php?tid={$thread[\'tid\']}\'>{$thread[\'subject\']}</a></td>
	<td id=\'{$thread[\'subid\']}\' name=\'sub_notes\' class=\'expmanager_editonclick\'><i>{$thread[\'sub_notes\']}</i></td>
	<td> {$submission_otherposters}</td>
</tr>'),
			"sid"		=> -2,
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
			"sid"		=> -2,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
	$expmanager_templates[6] = array(
			"title" 	=> "expmanage_thread_mod",
			"template"	=> $db->escape_string('<tr>
	<td><a target=\'_blank\' href=\'showthread.php?tid={$thread[\'tid\']}\'>{$thread[\'subject\']}</a></td>
	<td id=\'{$thread[\'subid\']}\' name=\'sub_notes\' class=\'expmanager_editonclick\'><i>{$thread[\'sub_notes\']}</i></td>
  	<td><a target=\'_blank\' href=\'member.php?action=profile&uid={$submission_user[\'uid\']}\'>{$submission_user[\'username\']}</a></td>
	<td>{$submission_otherposters}</td>
	<td><a href=\'javascript:void(0)\' id=\'{$submission_id}\' class=\'expapprove_button\'>Approve Request</a>
		<br> <a href=\'javascript:void(0)\' id=\'{$submission_id}\' class=\'expapprove_button_deny\'>Deny Request</a></td>
</tr>'),
			"sid"		=> -2,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
	$expmanager_templates[7] = array(
			"title" 	=> "expmanage_thread_usermod",
			"template"	=> $db->escape_string('<tr class=\'{$class}\'>
	<td>{$action}</td>
	<td><a target=\'_blank\' href=\'showthread.php?tid={$thread[\'tid\']}\'>{$thread[\'subject\']}</a></td>
	<td id=\'{$thread[\'subid\']}\' name=\'sub_notes\' class=\'expmanager_editonclick\'><i>{$thread[\'sub_notes\']}</i></td>
	<td>{$submission_otherposters}</td>
</tr>'),
			"sid"		=> -2,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
	$expmanager_templates[8] = array(
			"title" 	=> "expmanage_category_usermod",
			"template"	=> $db->escape_string('<h1>{$category[\'cat_name\']} (+{$category[\'cat_expamt\']} EXP)</h1>
<p style=\'margin-top:-10px;margin-bottom:10px;margin-left:50px;\'>{$category[\'cat_rules\']}</p>
						<table style=\'width:95%;margin:auto;text-align:center\'>
							<tr class=\'thead\' style=\'font-weight:bold\'>
								<td width=15%>Action?</td>
								<td width=30%>Thread</td>
								<td width=30%>Submission Notes</td>
								<td width=25%>Other Participants</td>
							</tr>
						{$threadlist}
            </table><br>
						<a href=\'javascript:void(0)\' id=\'{$category[\'catid\']}\' class=\'expfinalize_button button\'>Award EXP for Threads</a>
						<a href=\'javascript:void(0)\' id=\'{$category[\'catid\']}\' class=\'expfinalize_button_deny button\'>Deny EXP for Threads</a>'),
			"sid"		=> -2,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
	$expmanager_templates[9] = array(
			"title" 	=> "expmanage_dialog_submit",
			"template"	=> $db->escape_string('<div id="expdialog" class="modal current">
					<form>
					<table style="padding: 0px;" class="tborder" border="0" cellpadding="5" cellspacing="0" width="100%">
						<tbody><tr>
						<td class="thead" colspan="2"><strong>Submit Thread for Exp</strong></td>
						</tr>
						<tr>
						<td class="trow1" style="text-shadow: 1px 1px 0px #000;" width="25%"><strong>EXP Category:</strong></td>
						<td class="trow1"><select name="sub_catid[]" multiple>{$category_options}</select></td>
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
			"sid"		=> -2,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
	$expmanager_templates[10] = array(
			"title" 	=> "expmanage_link_profile",
			"template"	=> $db->escape_string('<tbody><tr><td class="trow1 smalltext"><a href="{$link}" class="usercp_nav_item modcp_nav_item"><i style="font-size: 14px;" class="fa fa-check-circle-o  fa-fw"></i> Manage User\'s EXP Threads</a></td></tr></tbody>'),
			"sid"		=> -2,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
  $expmanager_templates[11] = array(
    "title" => "expmanage_category_mod",
    "template" => $db->escape_string('<h1>{$category[\'cat_name\']} (+{$category[\'cat_expamt\']} EXP)</h1>
<p style=\'margin-top:-10px;margin-bottom:10px;margin-left:50px;\'>{$category[\'cat_rules\']}</p>
						<table style=\'width:95%;margin:auto;text-align:center\'>
							<tr class=\'thead\' style=\'font-weight:bold\'>
								<td width=20%>Thread</td>
								<td width=30%>Submission Notes</td>
								<td width=10%>User</td>
								<td width=20%>Other Participants</td>
								<td width=20%>Action</td>
							</tr>
						{$threadlist}
						</table>'),
    "sid" => -2,
    "version" => 1.0,
    "dateline" => TIME_NOW
  );
  $expmanager_templates[12] = array(
			"title" 	=> "expmanage_alert",
			"template"	=> $db->escape_string('<div class="pm_alert" style="background-color:#99D6EB !important;border-color:#3D565E !important">{$expmanage_alert_text}</div>'),
			"sid"		=> -2,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
  $expmanager_templates[13] = array(
      "title" 	=> "expmanage_fullview_usermod",
      "template"	=> $db->escape_string('<html><head>
                <title>{$mybb->settings[\'bbname\']} - EXP Manager</title>{$headerinclude}
                <style>
							    .submitted {
							      /* Thread not approved for EXP */
									background-color:#F0F0B2;
							    }
							    .approved {
							      /* Thread approved for EXP but points aren\'t yet awarded */
							      background-color:#A0CAAF;
							    }
							    .exp_applied {
							      /* Thread approved for EXP and points have been awarded for it */
							      background-color:#EAEAEA;
							    }
							  </style>
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
                          <br><br>
                          {$cancel_notify_button}
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
            {$footer}
            </body>
            </html>'),
      "sid"		=> -2,
      "version"	=> 1.0,
      "dateline"	=> TIME_NOW
  );
  $expmanager_templates[14] = array(
			"title" 	=> "expmanage_dialog_markawarded",
			"template"	=> $db->escape_string('<div id="expdialog2" class="modal current">
					<form>
					<table style="padding: 0px;" class="tborder" border="0" cellpadding="5" cellspacing="0" width="100%">
						<tbody><tr>
						<td class="thead" colspan="2"><strong>Mark Thread as Awarded</strong></td>
						</tr>
						<tr>
						<td class="trow1" style="text-shadow: 1px 1px 0px #000;" width="25%"><strong>EXP Category:</strong></td>
						<td class="trow1"><select name="sub_catid[]" multiple>{$category_options}</select></td>
						</tr>
						<tr>
						<td class="trow2" style="text-shadow: 1px 1px 0px #000;"><strong>Notes:</strong></td>
						<td class="trow2"><textarea name="sub_notes"></textarea></td>
						</tr>
						<tr>
						<td class="trow2" colspan="2">
						<input type="hidden" name="sub_tid" value="{$thread[\'tid\']}">
						<div align="center">
							<a href="javascript:void(0);" class="button expmark_button">Submit</a>
						</div></td>
						</tr>
						</tbody></table>
					</form>
				</div>'),
			"sid"		=> -2,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
  $expmanager_templates[15] = array(
			"title" 	=> "expmanage_buttons_notify",
			"template"	=> $db->escape_string('<div style=\'width:100%;text-align:center\'>
  	               <a href=\'javascript:void(0)\' id = \'{$mybb->user[\'uid\']}\' class=\'exprequestmoderation_button button\'>Request EXP for Approved Threads</a>
  	                <br><i>(If you believe approved threads are fit for EXP to be awarded, Mods will be notified)</i>
                    </div>'),
			"sid"		=> -2,
			"version"	=> 1.0,
			"dateline"	=> TIME_NOW
	);
  $expmanager_templates[16] = array(
			"title" 	=> "expmanage_buttons_cancelnotify",
			"template"	=> $db->escape_string('<div style=\'width:100%;text-align:center\'>
                    <a href=\'javascript:void(0)\' id = \'{$userid}\' class=\'exprequestmoderation_button_cancel button\'>Cancel Moderation Request</a>
                    <br><i>(Get rid of the notification if threads aren\'t ready)</i>
                    </div>'),
			"sid"		=> -2,
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
	find_replace_templatesets('showthread', '#{\$newreply}{\$expmanage_buttons_submit}#', '{\$newreply}');
  find_replace_templatesets('header', '#{\$awaitingusers}{\$expmanage_requests}#', '{\$awaitingusers}');
	// Then apply
	find_replace_templatesets('showthread', '#{\$newreply}#', '{\$newreply}{\$expmanage_buttons_submit}');
  find_replace_templatesets('header', '#{\$awaitingusers}#', '{\$awaitingusers}{\$expmanage_requests}');
}

/**
 * Deactivate Plugin
 */
function expmanager_deactivate()
{
	// Revert any Template Edits
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets('showthread', '#{\$newreply}{\$expmanage_buttons_submit}#', '{\$newreply}');
  find_replace_templatesets('header', '#{\$awaitingusers}{\$expmanage_requests}#', '{\$awaitingusers}');
}

/**
 * Uninstall Plugin
 */
function expmanager_uninstall()
{
	global $db;

	// Delete any templates
	$db->delete_query("templates", "`title` LIKE 'expmanage_%'");
  $db->delete_query("templategroups", "`prefix` = 'expmanage'");

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
  if ($db->table_exists("expmodrequests"))
  {
    $db->write_query("DROP TABLE `".TABLE_PREFIX."expmodrequests`");
  }

	// Delete settings
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name LIKE 'expmanager_%'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='expmanager'");
	rebuild_settings();
}

?>
