<?php
/***********************************************************************/

// Some info about your mod.
$mod_title      = 'Project Honey Pot http:BL Integration';
$mod_version    = '1.0';
$release_date   = '2007-05-19';
$author         = 'Smartys';
$author_email   = 'smartys@punbb-hosting.com';

// Versions of FluxBB this mod was created for. Minor variations (i.e. 1.2.4 vs 1.2.5) will be allowed, but a warning will be displayed.
$fluxbb_versions	= array('1.2', '1.2.1', '1.2.2', '1.2.3', '1.2.4', '1.2.5', '1.2.6', '1.2.7', '1.2.8', '1.2.9', '1.2.10', '1.2.11', '1.2.12', '1.2.13', '1.2.14', '1.2.15', '1.2.20', '1.2.21', '1.2.22');

// Set this to false if you haven't implemented the restore function (see below)
$mod_restore	= true;


// This following function will be called when the user presses the "Install" button
function install()
{
	global $db, $db_type, $pun_config;

	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db->prefix."httpbl (
					type INT(10) UNSIGNED NOT NULL DEFAULT 1,
					minimum_score INT(10) UNSIGNED NOT NULL DEFAULT 0,
					last_activity INT(10) UNSIGNED NOT NULL DEFAULT 0,
					PRIMARY KEY (type)
					) TYPE=MyISAM;";
			break;

		case 'pgsql':
			$sql = 'CREATE TABLE '.$db->prefix."httpbl (
					type INT NOT NULL DEFAULT 1,
					minimum_score INT NOT NULL DEFAULT 0,
					last_activity INT NOT NULL DEFAULT 0,
					PRIMARY KEY (type)
					)";
			break;

		case 'sqlite':
			$sql = 'CREATE TABLE '.$db->prefix."httpbl (
					type INTEGER NOT NULL DEFAULT 1,
					minimum_score INTEGER NOT NULL DEFAULT 0,
					last_activity INTEGER NOT NULL DEFAULT 0,
					PRIMARY KEY (type)
					)";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db->prefix.'httpbl',  __FILE__, __LINE__, $db->error());

	// Insert config data
	$config = array(
		'o_httpbl_enabled'			=> "'1'",
		'o_httpbl_access_key'		=> "''",
		'o_httpbl_ban_message'		=> "'Your IP has been banned.'",
		'o_httpbl_ban_expire'		=> "'86400'"
	);
	
	while (list($conf_name, $conf_value) = @each($config))
	{
		$db->query('INSERT INTO '.$db->prefix."config (conf_name, conf_value) VALUES('$conf_name', $conf_value)")
			or error('Unable to insert into table '.$db->prefix.'config. Please check your configuration and try again.');
	}

	// Delete all .php files in the cache (someone might have visited the forums while we were updating and thus, generated incorrect cache files)
	$d = dir(PUN_ROOT.'cache');
	while (($entry = $d->read()) !== false)
	{
		if (substr($entry, strlen($entry)-4) == '.php')
			@unlink(PUN_ROOT.'cache/'.$entry);
	}
	$d->close();
}

// This following function will be called when the user presses the "Restore" button (only if $mod_uninstall is true (see above))
function restore()
{
	global $db, $db_type, $pun_config;

	$db->query('DROP TABLE '.$db->prefix.'httpbl') or error('Unable to drop table', __FILE__, __LINE__, $db->error());
	$db->query('DELETE FROM '.$db->prefix.'config WHERE conf_name LIKE "o_httpbl_%"') or error('Unable to remove config entries', __FILE__, __LINE__, $db->error());;

	// Delete all .php files in the cache (someone might have visited the forums while we were updating and thus, generated incorrect cache files)
	$d = dir(PUN_ROOT.'cache');
	while (($entry = $d->read()) !== false)
	{
		if (substr($entry, strlen($entry)-4) == '.php')
			@unlink(PUN_ROOT.'cache/'.$entry);
	}
	$d->close();
}

/***********************************************************************/

// DO NOT EDIT ANYTHING BELOW THIS LINE!


// Circumvent maintenance mode
define('PUN_TURN_OFF_MAINT', 1);
define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

// We want the complete error message if the script fails
if (!defined('PUN_DEBUG'))
	define('PUN_DEBUG', 1);

// Make sure we are running a FluxBB version that this mod works with
$version_warning = false;
if(!in_array($pun_config['o_cur_version'], $fluxbb_versions))
{
	foreach ($fluxbb_versions as $temp)
	{
		if (substr($temp, 0, 3) == substr($pun_config['o_cur_version'], 0, 3))
		{
			$version_warning = true;
			break;
		}
	}

	if (!$version_warning)
		exit('You are running a version of FluxBB ('.$pun_config['o_cur_version'].') that this mod does not support. This mod supports FluxBB versions: '.implode(', ', $fluxbb_versions));
}


$style = (isset($cur_user)) ? $cur_user['style'] : $pun_config['o_default_style'];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php echo $mod_title ?> installation</title>
<link rel="stylesheet" type="text/css" href="style/<?php echo $pun_config['o_default_style'].'.css' ?>" />
</head>
<body>

<div id="punwrap">
<div id="puninstall" class="pun" style="margin: 10% 20% auto 20%">

<?php

if (isset($_POST['form_sent']))
{
	if (isset($_POST['install']))
	{
		// Run the install function (defined above)
		install();

?>
<div class="block">
	<h2><span>Installation successful</span></h2>
	<div class="box">
		<div class="inbox">
			<p>Your database has been successfully prepared for <?php echo pun_htmlspecialchars($mod_title) ?>. See readme.txt for further instructions.</p>
		</div>
	</div>
</div>
<?php

	}
	else
	{
		// Run the restore function (defined above)
		restore();

?>
<div class="block">
	<h2><span>Restore successful</span></h2>
	<div class="box">
		<div class="inbox">
			<p>Your database has been successfully restored.</p>
		</div>
	</div>
</div>
<?php

	}
}
else
{

?>
<div class="blockform">
	<h2><span>Mod installation</span></h2>
	<div class="box">
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>?foo=bar">
			<div><input type="hidden" name="form_sent" value="1" /></div>
			<div class="inform">
				<p>This script will update your database to work with the following modification:</p>
				<p><strong>Mod title:</strong> <?php echo pun_htmlspecialchars($mod_title).' '.$mod_version ?></p>
				<p><strong>Author:</strong> <?php echo pun_htmlspecialchars($author) ?> (<a href="mailto:<?php echo pun_htmlspecialchars($author_email) ?>"><?php echo pun_htmlspecialchars($author_email) ?></a>)</p>
				<p><strong>Disclaimer:</strong> Mods are not officially supported by FluxBB. Mods generally can't be uninstalled without running SQL queries manually against the database. Make backups of all data you deem necessary before installing.</p>
<?php if ($mod_restore): ?>				<p>If you've previously installed this mod and would like to uninstall it, you can click the restore button below to restore the database.</p>
<?php endif; ?><?php if ($version_warning): ?>				<p style="color: #a00"><strong>Warning:</strong> The mod you are about to install was not made specifically to support your current version of FluxBB (<?php echo $pun_config['o_cur_version']; ?>). However, in most cases this is not a problem and the mod will most likely work with your version as well. If you are uncertain about installning the mod due to this potential version conflict, contact the mod author.</p>
<?php endif; ?>			</div>
			<p><input type="submit" name="install" value="Install" /><?php if ($mod_restore): ?><input type="submit" name="restore" value="Restore" /><?php endif; ?></p>
		</form>
	</div>
</div>
<?php

}

?>

</div>
</div>

</body>
</html>