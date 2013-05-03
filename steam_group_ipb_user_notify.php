<?php
/*
 *   Steam Group IPB User Notification
 *
 *   A simple cronjob to summarize and check usergroups between an IPB install 
 *   and a Steam Community group.  Lines can be un-commented to automate IPB 
 *   permission changes.  By default it will output the information into 
 *   sections and take no action.
 *
 *   Copyright (C) 2013  Jake "rannmann" Forrester
 *   rannmann@rannmann.com
 *   https://firepoweredgaming.com
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

header("Content-Type: text/plain");

//-----------------------------------------
// Config
//-----------------------------------------
$STEAM_GROUP_NAME= 'FirePoweredMembers'; // Steam group name as displayed in URL
$USER_GROUP_ID   = 3; // User: This should be the default user group ID on the forums
$MEMBER_GROUP_ID = 7; // Member: This should be the group ID for members in your community/clan
$CHECK_GROUPS    = '7,10,11,20'; // Extra groups to check beyond just those in USER_GROUP_ID: (Member/Moderator/Trade Mod/Senior Member)

// Email contents.  If you want some output before the user dumps, add that here.
$output          = "Note: Forum statstics only apply to users in the following groups: User/Member/Moderator/Trade Mod/Senior Member\n\n";


//-----------------------------------------
// Requirements
//-----------------------------------------
// Setup the IPB framework
define( 'IPS_PUBLIC_SCRIPT', '/home/username/firepoweredgaming.com/forums/index.php' ); // Change this to your forums/index.php
define( 'IPB_THIS_SCRIPT', 	 'public' );
define( 'CCS_GATEWAY_CALLED', true );
require_once( '/home/username/firepoweredgaming.com/forums/initdata.php' ); // Change this to your forums/initdata.php
require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );
// Create our registry 
$registry = ipsRegistry::instance();
$registry->init();

// Some counters
$unlinked_user_in_count   = 0;
$linked_user_in_count     = 0;
$linked_user_not_in_count = 0;


//-----------------------------------------
// Grab a list of all the users on the forums
//-----------------------------------------
ipsRegistry::DB()->build( array(
      'select'  => 'member_id, name, member_group_id, steamid',
      'from'    => array('members' => 'm'),
      'where'   => 'member_group_id in ('.$USER_GROUP_ID.','.$CHECK_GROUPS.')'
    ));
$q = ipsRegistry::DB()->execute();
if (ipsRegistry::DB()->getTotalRows() > 0)
{
	while( $u = ipsRegistry::DB()->fetch( $q ) ) 
	{
		if ($u['steamid']) 
		{
			$linked_users[] = array(
				'member_id'       => $u['member_id'],
				'steamid'         => $u['steamid'],
				'member_group_id' => $u['member_group_id'],
				'name'            => $u['name']
			);
		} 
		else 
		{
			$unlinked_users[] = array(
				'member_id'       => $u['member_id'],
				'member_group_id' => $u['member_group_id'],
				'name'            => $u['name']
			);
		}
	}

	//-----------------------------------------
	// If a user isn't steam linked, move them
	// to the "user" group.
	//-----------------------------------------
	if ($unlinked_users)
	{
		$output .= "###############################################\n# UNLINKED ACCOUNTS IN MEMBER GROUP OR HIGHER #\n# Might need demoted on forums & steam kicked #\n###############################################\n";
		foreach($unlinked_users as &$unlinked_user)
		{
			if ($unlinked_user['member_group_id'] != $USER_GROUP_ID)
			{
				$output .= "\n\n".$unlinked_user['name']."\n\tmember_id\t=>".$unlinked_user['member_id']."\n\tmember_group_id\t=>".$unlinked_user['member_group_id'];
				$unlinked_user_in_count++;

				// BAD USER! You need a steam linked account to stay in the member group.  Get back into the default user group!
				// IPSMEMBER::save( $unlinked_user['member_id'], array( 'members' => array('member_group_id' => $USER_GROUP_ID ) ) );
			}
		}
	}
	

	//-----------------------------------------
	// If at least one user is steam linked, check if
	// in the steam group
	//-----------------------------------------
	if ($linked_users)
	{
		// Get the group data
		$group   = file_get_contents('http://steamcommunity.com/groups/'.$STEAM_GROUP_NAME.'/memberslistxml/') or die('Unable to connect to Steam Community.  Halting.');
		$group   = simplexml_load_string($group);
		$members = get_object_vars($group->members);
		$members = $members['steamID64'];

		
		foreach($linked_users as &$linked_user)
		{
			// Remove the "member" if they are not in the steam group.
			if ($linked_user['member_group_id'] != $USER_GROUP_ID && !in_array($linked_user['steamid'],$members))
			{
				$linked_user_not_in .= "\n\n".$linked_user['name']."\n\tmember_id\t=> ".$linked_user['member_id']."\n\tsteamid\t\t=> ".$linked_user['steamid']."\n\tmember_group_id\t=> ".$linked_user['member_group_id']."\n\tProfile\t\t=> http://steamcommunity.com/profiles/".$linked_user['steamid'];
				// BAD USER! Get back into the default "User" group if you have left the Steam group!
				// IPSMEMBER::save( $linked_user['member_id'], array( 'members' => array('member_group_id' => $USER_GROUP_ID ) ) );
				$linked_user_not_in_count++;
			}
			// Add the "user" to the "member" group if in the steam group.
			elseif ($linked_user['member_group_id'] == $USER_GROUP_ID && in_array($linked_user['steamid'],$members))
			{
				$linked_user_in .= "\n\n".$linked_user['name']."\n\tmember_id\t=> ".$linked_user['member_id']."\n\tsteamid\t\t=> ".$linked_user['steamid']."\n\tmember_group_id\t=> ".$linked_user['member_group_id'];
				// GOOD USER!  User in the steam user group--Here, have a promotion to "Member" status!
				// IPSMEMBER::save( $linked_user['member_id'], array( 'members' => array('member_group_id' => $MEMBER_GROUP_ID ) ) );
				$linked_user_in_count++;
			}
		}
		$output .= "\n\n#############################################\n#    FORUM \"MEMBERS\" NOT IN STEAM GROUP     #\n#     Might need invited to steam group     #\n#############################################";
		$output .= $linked_user_not_in;
		$output .= "\n\n#############################################\n#           \"USERS\" IN STEAM GROUP          #\n#       Might need promoted on forums       #\n#############################################\n";
		$output .= $linked_user_in;
	}

	$output .= "\n\n#############################################\n#                TOTAL STATS                #\n#############################################\n";
	$output .= "\nUnlinked users: \t\t\t ".count($unlinked_users);
	$output .= "\nUnlinked users in steam group:\t\t $unlinked_user_in_count ".( $unlinked_user_in_count > 0 ? "(NEEDS ATTENTION)" : "");
	$output .= "\nSteam-Linked users:\t\t\t ".count($linked_users);
	$output .= "\nSteam-Linked members not in steam group: $linked_user_not_in_count ".( $linked_user_not_in_count > 0 ? "(NEEDS ATTENTION)" : "");
	$output .= "\nSteam-Linked non-members in steam group: $linked_user_in_count ".( $linked_user_in_count > 0 ? "(NEEDS ATTENTION)" : "");
	$output .= "\nSteam Group Members:\t\t\t ".count($members);

	echo $output;

} else {
	echo 'There are no users in the specified groups.  This is probably a big deal and needs to be fixed, fer srsly.';
}
?>