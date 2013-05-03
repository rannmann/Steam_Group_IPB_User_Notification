Steam Group IPB User Notification
=========

by Jake "rannmann" Forrester
for [FirePowered Gaming](https://firepoweredgaming.com/forums)

PHP script to be run as a cronjob that, by default, displays a list of users from IPB groups (set in the config) that differ from those in a Steam group.  The following things are listed when the script runs:

* Each user with an unlinked steam account who are in the defined Steam group.
* Each user with a steam-linked account who are in the defined "member" IPB group, but not in the Steam group.
* Each user in the steam group who is in the default "user" group, rather than the member or other defined search groups.
* Total number of users in the defined search groups who are not Steam-linked.
* Total number of users in the steam group who are not Steam-linked on the forums.
* Total number of Steam-linked users.
* Total number of Steam-linked users in the defined "members" group who are not in the Steam group.
* Total number of Steam-linked users in the defined "users" (default) group who are in the Steam group.
* Total number of Steam group members.


Notes
-------

If you run this from cron, as is intended, make sure you use **full paths** to your IPB installation in the config.


Example Output
-------
    Note: Forum statstics only apply to users in the following groups: User/Member/Moderator/Trade Mod/Senior Member
    
    ###############################################
    # UNLINKED ACCOUNTS IN MEMBER GROUP OR HIGHER #
    # Might need demoted on forums & steam kicked #
    ###############################################
    
    
    #############################################
    #    FORUM "MEMBERS" NOT IN STEAM GROUP     #
    #     Might need invited to steam group     #
    #############################################
    
    Username
        member_id       => 74
        steamid         => 76561198036835000
        member_group_id => 7
        Profile         => http://steamcommunity.com/profiles/76561198036835000
    
    Jack
        member_id       => 94
        steamid         => 76561197973030000
        member_group_id => 7
        Profile         => http://steamcommunity.com/profiles/76561197973030000
        
    Jill
        member_id       => 146
        steamid         => 76561198068200000
        member_group_id => 7
        Profile         => http://steamcommunity.com/profiles/76561198068200000
    
    #############################################
    #           "USERS" IN STEAM GROUP          #
    #       Might need promoted on forums       #
    #############################################
    
    
    #############################################
    #                TOTAL STATS                #
    #############################################
    
    Unlinked users:                          27
    Unlinked users in steam group:           0 
    Steam-Linked users:                      132
    Steam-Linked members not in steam group: 3 (NEEDS ATTENTION)
    Steam-Linked non-members in steam group: 0 
    Steam Group Members:                     62