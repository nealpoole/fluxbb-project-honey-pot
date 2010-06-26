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

if ($pun_config['o_httpbl_enabled'] == '1')
{
	$response = query_httpbl($_SERVER['REMOTE_ADDR']);

	if (strpos($response, '.') === false)
		return;

	list(, $last_activity, $threat_score, $type) = explode('.', $response);

	// We fetch our information on how to treat this type of bot
	$result = $db->query('SELECT minimum_score, last_activity FROM '.$db->prefix.'httpbl WHERE type='.$type) or error('Could not fetch httpbl data', __FILE__, __LINE__, $db->error());

	// If there's no row, we take no action
	if ($db->num_rows($result) < 1)
		return;

	$threat_data = $db->fetch_assoc($result);

	// If we have more than the minimum threat score and have been active more recently than the last_activity
	// setting, we add the IP to a ban (so we don't have to do any more lookups on the IP for some period of time)
	// and display the admin's message
	if ($threat_data['minimum_score'] <= $threat_score && $threat_data['last_activity'] >= $last_activity)
	{
		$db->query('INSERT INTO '.$db->prefix.'bans (ip, message, expire) VALUES("'.$_SERVER['REMOTE_ADDR'].'", "'.$db->escape($pun_config['o_httpbl_ban_message']).'", '.(time() + $pun_config['o_httpbl_ban_expire']).')') or error('Unable to add ban', __FILE__, __LINE__, $db->error());

		// Regenerate the bans cache
		require_once PUN_ROOT.'include/cache.php';
		generate_bans_cache();

		message($pun_config['o_httpbl_ban_message']);
	}
}

// This function simplifies sending the lookup request
function query_httpbl ($ip)
{
	global $pun_config;

	list($a, $b, $c, $d) = explode('.', $ip);

	$hostname = $pun_config['o_httpbl_access_key'].'.'.$d.'.'.$c.'.'.$b.'.'.$a.'.dnsbl.httpbl.org';
	$ip = gethostbyname($hostname);

	return ($ip == $hostname) ? NULL : $ip;
}