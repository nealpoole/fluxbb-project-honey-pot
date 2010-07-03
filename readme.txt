##
##
##        Mod title:  Project Honey Pot http:BL Integration
##
##      Mod version:  1.1
##  Works on FluxBB:  1.4.*
##     Release date:  2007-05-19
##           Author:  Smartys (smartys@punbb-hosting.com)
##
##      Description:  This mod integrates Project Honey Pot's http:BL service
##                    into FluxBB's registration, login, and posting systems.
##                    You can find more information on http:BL at:
##                    http://www.projecthoneypot.org/httpbl.php
##
##   Affected files:  post.php
##                    register.php
##                    login.php
##
##       Affects DB:  Yes
##
##            Notes:  In order to take advantage of this mod, you will need an
##                    "Access Key." For more information on obtaining a key,
##                    please read: http://www.projecthoneypot.org/httpbl.php
##
##       DISCLAIMER:  Please note that "mods" are not officially supported by
##                    FluxBB. Installation of this modification is done at your
##                    own risk. Backup your forum database and any and all
##                    applicable files before proceeding.
##
##


#
#---------[ 1. UPLOAD ]-------------------------------------------------------
#

install_mod.php to /
httpbl.php to /include/
AP_httpBL.php to /plugins/

#
#---------[ 2. RUN ]----------------------------------------------------------
#

install_mod.php


#
#---------[ 3. DELETE ]-------------------------------------------------------
#

install_mod.php


#
#---------[ 4. OPEN ]---------------------------------------------------------
#

login.php


#
#---------[ 5. FIND ]---------------------------------------------
#

$username_sql = ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb') ? 'username=\''.$db->escape($form_username).'\'' : 'LOWER(username)=LOWER(\''.$db->escape($form_username).'\')';


#
#---------[ 6. AFTER, ADD ]-------------------------------------------------
#

require PUN_ROOT.'include/httpbl.php';


#
#---------[ 7. OPEN ]---------------------------------------------------------
#

register.php


#
#---------[ 8. FIND ]---------------------------------------------
#

message($lang_register['Registration flood']);


#
#---------[ 9. AFTER, ADD ]-------------------------------------------------
#

require PUN_ROOT.'include/httpbl.php';


#
#---------[ 10. OPEN ]---------------------------------------------------------
#

post.php


#
#---------[ 11. FIND ]------------------------------------------
#

// Flood protection
if (!isset($_POST['preview']) && $pun_user['last_post'] != '' && (time() - $pun_user['last_post']) < $pun_user['g_post_flood'])
	$errors[] = $lang_post['Flood start'].' '.$pun_user['g_post_flood'].' '.$lang_post['flood end'];

#
#---------[ 12. AFTER, ADD ]-------------------------------------------------
#

if ($pun_user['is_guest'])
	require PUN_ROOT.'include/httpbl.php';


#
#---------[ 13. SAVE/UPLOAD ]-------------------------------------------------
#