<?php
/***********************************************************************

  Copyright (C) 2007  Smartys (smartys@punbb-hosting.com)

  This file is part of PunBB.

  PunBB is free software; you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 2 of the License,
  or (at your option) any later version.

  PunBB is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston,
  MA  02111-1307  USA

************************************************************************/

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);

if (isset($_POST['add_type']))
{
	$type = round($_POST['threat_type']);
	$minimum_threat = max(round($_POST['minimum_threat']), 0);
	$last_activity = max(round($_POST['last_activity']), 0);

	// We need to make sure there is not already an action for that type
	$result = $db->query('SELECT 1 FROM '.$db->prefix.'httpbl WHERE type='.$type) or error('Unable to check type', __FILE__, __LINE__, $db->error());

	if ($db->num_rows($result) > 0)
		message('There are already rules defined for that type, please edit them instead!');
		
	$db->query('INSERT INTO '.$db->prefix.'httpbl (type, minimum_score, last_activity) VALUES ('.$type.', '.$minimum_threat.', '.$last_activity.')') or error('Unable to add type rules', __FILE__, __LINE__, $db->error());

	redirect($_SERVER['REQUEST_URI'], 'Successfully added new type. Redirecting...');
}
else if (isset($_POST['update_type']))
{
	$type = round(key($_POST['update_type']));
	$minimum_threat = max(round($_POST['minimum_threat_edit'][$type]), 0);
	$last_activity = max(round($_POST['last_activity_edit'][$type]), 0);

	$db->query('UPDATE '.$db->prefix.'httpbl SET minimum_score='.$minimum_threat.', last_activity='.$last_activity.' WHERE type='.$type) or error('Unable to edit type rules', __FILE__, __LINE__, $db->error());

	redirect($_SERVER['REQUEST_URI'], 'Successfully edited type. Redirecting...');
}
else if (isset($_POST['remove_type']))
{
	$db->query('DELETE FROM '.$db->prefix.'httpbl WHERE type='.round(key($_POST['remove_type']))) or error('Unable to delete type rules', __FILE__, __LINE__, $db->error());

	redirect($_SERVER['REQUEST_URI'], 'Successfully deleted type. Redirecting...');
}
else if (isset($_POST['update_settings']))
{
	$form = array_map('trim', $_POST['form']);

	if (!isset($form['httpbl_enabled']) || $form['httpbl_enabled'] != '1') $form['httpbl_enabled'] = '0';
	$form['httpbl_ban_expire'] = round($form['httpbl_ban_expire']);

	while (list($key, $input) = @each($form))
	{
		// Only update option values that have changed
		if (array_key_exists('o_'.$key, $pun_config) && $pun_config['o_'.$key] != $input)
		{
			if ($input != '' || is_int($input))
				$value = '\''.$db->escape($input).'\'';
			else
				$value = 'NULL';

			$db->query('UPDATE '.$db->prefix.'config SET conf_value='.$value.' WHERE conf_name=\'o_'.$db->escape($key).'\'') or error('Unable to update board config', __FILE__, __LINE__, $db->error());
		}
	}
	
	// Regenerate the config cache
	require_once PUN_ROOT.'include/cache.php';
	generate_config_cache();
	
	redirect($_SERVER['REQUEST_URI'], 'Successfully updated settings. Redirecting...');
}
else	// If not, we show the "Show text" form
{
	// Display the admin navigation menu
	generate_admin_menu($plugin);

?>
	<div id="httpblplugin" class="blockform">
		<h2><span>Project Honey Pot http:BL Plugin</span></h2>
		<div class="box">
			<div class="inbox">
				<p>This plugin allows you to choose your settings for how to deal with the various different threats detected by the http:BL.</p>
			</div>
		</div>

		<h2 class="block2"><span>Basic Settings</span></h2>
		<div class="box">
			<form id="settings" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
				<p class="submittop"><input type="submit" name="update_settings" value="Save changes" /></p>
				<div class="inform">
					<fieldset>
						<legend>Basic Settings</legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<th scope="row">Enable checking</th>
								<td>
									<input type="radio" name="form[httpbl_enabled]" value="1"<?php if ($pun_config['o_httpbl_enabled'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[httpbl_enabled]" value="0"<?php if ($pun_config['o_httpbl_enabled'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
									<span>Enables or disables this modificaton.</span>
								</td>
							</tr>
							<tr>
								<th scope="row">Access Key</th>
								<td>
									<input type="text" name="form[httpbl_access_key]" size="50" value="<?php echo pun_htmlspecialchars($pun_config['o_httpbl_access_key']) ?>" />
									<span>Required for this modification to work. More information on obtaining an access key can be found <a href="http://www.projecthoneypot.org/httpbl.php">here</a>.</span>
								</td>
							</tr>
							<tr>
								<th scope="row">Ban Message</th>
								<td>
									<textarea name="form[httpbl_ban_message]" rows="5" cols="55"><?php echo pun_htmlspecialchars($pun_config['o_httpbl_ban_message']) ?></textarea>
									<span>This message will be shown to a user when their IP is listed in the http:BL and matches the criteria you set. This message will also be used as their ban message.</span>
								</td>
							</tr>
							<tr>
								<th scope="row">Ban expiration</th>
								<td>
									<input type="text" name="form[httpbl_ban_expire]" size="5" maxlength="5" value="<?php echo $pun_config['o_httpbl_ban_expire'] ?>" />
									<span>Number of seconds an IP will be banned for when it is detected by this modification.</span>
								</td>
							</tr>
						</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="update_settings" value="Save changes" /></p>
			</form>
		</div>
		<h2 class="block2"><span>Actions</span></h2>
		<div class="box">
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
				<div class="inform">
					<fieldset>
						<legend>Add type</legend>
						<div class="infldset">
							<p>Choose a type of bot, the minimum threat level of the bot, and the maximum level of "staleness" (number of days since the last observed action by the bot). These settings will apply for all bots of the given type. It is suggested that you define actions for all types of bots.</p>
							<table  cellspacing="0">
							<thead>
								<tr>
									<th class="tcl" scope="col">Type of Threat</th>
									<th class="tc2" scope="col">Threat level</th>
									<th class="tc3" scope="col">Last Activity</th>
									<th class="hidehead" scope="col">Action</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>
										<select name="threat_type">
											<option value="1">Suspicious</option>
											<option value="2">Harvester</option>
											<option value="3">Suspicious &amp; Harvester</option>
											<option value="4">Comment Spammer</option>
											<option value="5">Suspicious &amp; Comment Spammer</option>
											<option value="6">Harvester &amp; Comment Spammer</option>
											<option value="7">Suspicious &amp; Harvester &amp; Comment Spammer</option>
										</select>
									</td>
									<td><input type="text" name="minimum_threat" size="7" maxlength="7" tabindex="1" /></td>
									<td><input type="text" name="last_activity" size="7" maxlength="7" tabindex="2" /></td>
									<td><input type="submit" name="add_type" value=" Add " tabindex="3" /></td>
								</tr>
							</tbody>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend>Edit/remove types</legend>
						<div class="infldset">
							<table  cellspacing="0">
							<thead>
								<tr>
									<th class="tcl" scope="col">Type of Threat</th>
									<th class="tc2" scope="col">Threat level</th>
									<th class="tc3" scope="col">Last Activity</th>
									<th class="hidehead" scope="col">Actions</th>
								</tr>
							</thead>
							<tbody>
<?php
$result = $db->query('SELECT * FROM '.$db->prefix.'httpbl ORDER BY type ASC') or error('Unable to fetch created types', __FILE__, __LINE__, $db->error());
while ($data = $db->fetch_assoc($result))
{
	switch ($data['type'])
	{
		case 1:
			$data['type_name'] = 'Suspicious';
			break;

		case 2:
			$data['type_name'] = 'Harvester';
			break;

		case 3:
			$data['type_name'] = 'Suspicious &amp; Harvester';
			break;

		case 4:
			$data['type_name'] = 'Comment Spammer';
			break;

		case 5:
			$data['type_name'] = 'Suspicious &amp; Comment Spammer';
			break;

		case 6:
			$data['type_name'] = 'Harvester &amp; Comment Spammer';
			break;

		case 7:
			$data['type_name'] = 'Suspicious &amp; Harvester &amp; Comment Spammer';
			break;

		default:
			$data['type_name'] = 'Unknown';
			break;
	}
?>
								<tr>
									<td><?php echo $data['type_name'] ?></td>
									<td><input type="text" name="minimum_threat_edit[<?php echo $data['type'] ?>]" value="<?php echo $data['minimum_score'] ?>" size="7" maxlength="7" /></td>
									<td><input type="text" name="last_activity_edit[<?php echo $data['type'] ?>]" value="<?php echo $data['last_activity'] ?>" size="7" maxlength="7" /></td>
									<td><input type="submit" name="update_type[<?php echo $data['type'] ?>]" value="Update" />&nbsp;<input type="submit" name="remove_type[<?php echo $data['type'] ?>]" value="Remove" /></td>
								</tr>
<?php
}
?>
							</tbody>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
	</div>
<?php

}

// Note that the script just ends here. The footer will be included by admin_loader.php.
