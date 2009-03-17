<?php
/**
* @version $Id$
* Kunena Component
* @package Kunena
*
* @Copyright (C) 2008 - 2009 Kunena Team All rights reserved
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @link http://www.kunena.com
*
* Based on FireBoard Component
* @Copyright (C) 2006 - 2007 Best Of Joomla All rights reserved
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @link http://www.bestofjoomla.com
*
* Based on Joomlaboard Component
* @copyright (C) 2000 - 2004 TSMF / Jan de Graaff / All Rights Reserved
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author TSMF & Jan de Graaff
**/

// Dont allow direct linking
defined( '_JEXEC' ) or die('Restricted access');

global $mainframe;

// Just for debugging and performance analysis
$mtime = explode(" ", microtime());
$tstart = $mtime[1] + $mtime[0];

// TODO: Get rid of THIS!!! - Kill notices (we have many..)
error_reporting (E_ALL ^ E_NOTICE);

// Kunena wide defines
require_once (JPATH_BASE  .DS. 'components' .DS. 'com_kunena' .DS. 'lib' .DS. 'kunena.defines.php');

global $fbIcons;
global $is_Moderator;

// ERROR: global scope mix
global $message;

// Get all the variables we need and strip them in case
$action 		= JRequest::getVar('action', '');

// FIXME: move this to post.php
$attachfile 	= JRequest::getVar('attachfile', '', 'FILES');
$attachimage 	= JRequest::getVar('attachimage', '', 'FILES');
$attachfile = $attachfile['name'];
$attachimage = $attachfile['name'];

$catid 			= JRequest::getInt('catid', 0);
$contentURL 	= JRequest::getVar('contentURL', '');
$do 			= JRequest::getVar('do', '');
$email 			= JRequest::getVar('email', '');
$favoriteMe 	= JRequest::getVar('favoriteMe', '');
$fb_authorname 	= JRequest::getVar('fb_authorname', '');
$fb_thread 		= JRequest::getInt('fb_thread', 0);
$func 			= strtolower(JRequest::getVar('func', ''));
$id 			= JRequest::getVar('id', '');
$limit 			= JRequest::getInt('limit', 0);
$limitstart 	= JRequest::getInt('limitstart', 0);
$markaction 	= JRequest::getVar('markaction', '');
$message 		= JRequest::getVar('message', '');
$page 			= JRequest::getInt('page', 0);
$parentid 		= JRequest::getInt('parentid', 0);
$pid 			= JRequest::getInt('pid', 0);
$replyto 		= JRequest::getInt('replyto', 0);
$resubject 		= JRequest::getVar('resubject', '');
$return 		= JRequest::getVar('return', '');
$rowid 			= JRequest::getInt('rowid', 0);
$rowItemid 		= JRequest::getInt('rowItemid', 0);
$sel 			= JRequest::getVar('sel', '');
$subject 		= JRequest::getVar('subject', '');
$subscribeMe 	= JRequest::getVar('subscribeMe', '');
$thread 		= JRequest::getInt('thread', 0);
$topic_emoticon = JRequest::getVar('topic_emoticon', '');
$userid 		= JRequest::getInt('userid', 0);
$view 			= JRequest::getVar('view', '');
$msgpreview 	= JRequest::getVar('msgpreview', '');

// Debug helpers
include_once (KUNENA_PATH_LIB .DS. "kunena.debug.php");

// get Kunenas configuration params in
require_once (KUNENA_PATH_LIB .DS. "kunena.config.class.php");

// Get CKunanaUser and CKunenaUsers
require_once (KUNENA_PATH_LIB .DS. "kunena.user.class.php");

global $fbConfig, $KunenaUser;

$my = &JFactory::getUser();

// Get data about the current user - its ok to not have a userid = guest
$KunenaUser = new CKunenaUser($my->id);
// Load configuration and personal settings for current user
$fbConfig = new CKunenaConfig($KunenaUser);

global $lang, $fbIcons;
global $is_Moderator;

// ERROR: global scope mix
global $message;

// Central Location for all internal links
require_once (KUNENA_PATH_LIB .DS. "kunena.link.class.php");

// Class structure should be used after this and all the common task should be moved to this class
require_once (KUNENA_PATH .DS. "class.kunena.php");

// get right Language file
if (file_exists(KUNENA_FILE_LANGUAGE)) {
    include_once (KUNENA_FILE_LANGUAGE);
} else {
    include_once (KUNENA_FILE_LANGUAGE_DEFAULT);
}

// Include Clexus PM class file
if ($fbConfig->pm_component == "clexuspm")
{
    require_once (KUNENA_ROOT_PATH .DS. 'components/com_mypms/class.mypms.php');
    $ClexusPMconfig = new ClexusPMConfig();
}

//time format
include_once (KUNENA_PATH_LIB .DS. 'kunena.timeformat.class.php');

$systime = time() + $fbConfig->board_ofset * KUNENA_SECONDS_IN_HOUR;

// Retrieve current cookie data for session handling
$settings = $_COOKIE['fboard_settings'];

$board_title = $fbConfig->board_title;
$fromBot = 0;
$prefview = $fbConfig->default_view;

// JOOMLA STYLE CHECK
if ($fbConfig->joomlastyle < 1) {
    $boardclass = "fb_";
    }

// Include Badword class file
if ($fbConfig->badwords and !class_exists('Badword')) {
	foreach (array('badwords2','badword') as $com_bw) {
		$com_bw = KUNENA_ROOT_PATH .DS. 'components/com_'.$com_bw.'/class.'.$com_bw.'.php';
		if (is_file($com_bw)) {
			require_once ($com_bw);
			break;
		}
	}
}

// Include preview here before inclusion of other files
if ($func == "getpreview") {

    if (file_exists(KUNENA_ABSTMPLTPATH .DS. 'smile.class.php')) {
        include (KUNENA_ABSTMPLTPATH .DS. 'smile.class.php');
    }
    else {
        include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'smile.class.php');
    }

    $message = utf8_urldecode(utf8_decode(stripslashes($msgpreview)));

    $msgbody = smile::smileReplace( $message , 0, $fbConfig->disemoticons, $smileyList);
    $msgbody = nl2br($msgbody);
    $msgbody = str_replace("__FBTAB__", "\t", $msgbody);
    // $msgbody = ereg_replace('%u0([[:alnum:]]{3})', '&#x1;',$msgbody);

    $msgbody = smile::htmlwrap($msgbody, $fbConfig->wrap);
    header("Content-Type: text/html; charset=utf-8");
    echo $msgbody;
    $mainframe->close();
}

if ($func == "showcaptcha") {
   include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/captcha/randomImage.php');
   $mainframe->close();
}

$document =& JFactory::getDocument();

// Add required header tags
if (defined('KUNENA_JQURL'))
{
	$document->addCustomTag('<script type="text/javascript" src="' . KUNENA_JQURL . '"></script>');
}

// inline jscript with image location
$document->addCustomTag('<script type="text/javascript">
jr_expandImg_url = "' . KUNENA_URLIMAGESPATH . '";</script>');

if (defined('KUNENA_COREJSURL'))
{
	$document->addCustomTag('<script type="text/javascript" src="' . KUNENA_COREJSURL . '"></script>');
}

if ($fbConfig->joomlastyle < 1) {
        if (file_exists(KUNENA_JTEMPLATEPATH.'/css/kunena.forum.css')) {
           $document->addCustomTag('<link type="text/css" rel="stylesheet" href="' . KUNENA_JTEMPLATEURL . '/css/kunena.forum.css" />');
                }
        else {
         $document->addCustomTag('<link type="text/css" rel="stylesheet" href="' . KUNENA_TMPLTCSSURL . '" />');
         }
    }
else {
   $document->addCustomTag('<link type="text/css" rel="stylesheet" href="' . KUNENA_DIRECTURL . '/template/default/joomla.css" />');
    }

// WHOIS ONLINE IN FORUM
if (file_exists(KUNENA_ABSTMPLTPATH . '/plugin/who/who.class.php')) {
    include (KUNENA_ABSTMPLTPATH . '/plugin/who/who.class.php');
    }
else {
    include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/who/who.class.php');
    }

// include required libraries
if (file_exists(KUNENA_ABSTMPLTPATH . '/fb_layout.php')) {
    require_once (KUNENA_ABSTMPLTPATH . '/fb_layout.php');
    }
else {
    require_once (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'fb_layout.php');
    }

require_once (KUNENA_PATH_LIB .DS. 'kunena.permissions.php');
require_once (KUNENA_PATH_LIB .DS. 'kunena.category.class.php');
require_once (JPATH_BASE.'/libraries/joomla/template/template.php');

if ($catid != '') {
    $thisCat = new jbCategory($database, $catid);
    }

$KunenaTemplate = new patTemplate();
$KunenaTemplate->setRoot( KUNENA_ABSTMPLTPATH );

$KunenaTemplate->readTemplatesFromFile("header.html");
$KunenaTemplate->readTemplatesFromFile("footer.html");

// Permissions: Check for administrators and moderators
if ($my->id != 0)
{
    $acl = &JFactory::getACL();
    $aro_group = $acl->getAroGroup($my->id);
    if ($aro_group)	$aro_group->group_id = $aro_group->id;  // changed fieldname in Joomla 1.5: "group_id" -> "id"
    $is_admin = (strtolower($aro_group->name) == 'super administrator' || strtolower($aro_group->name) == 'administrator');
}
else
{
    $aro_group = 0;
    $is_admin = 0;
}

$is_Moderator = fb_has_moderator_permission($database, $thisCat, $my->id, $is_admin);

//intercept the RSS request; we should stop afterwards
if ($func == 'fb_rss')
{
    include (KUNENA_PATH_LIB .DS. 'kunena.rss.php');
    $mainframe->close();
}

if ($func == 'fb_pdf')
{
    include (KUNENA_PATH_LIB .DS. 'kunena.pdf.php');
    $mainframe->close();
}

if ($func == '') // Set default start page as per config settings
{
	switch ($fbConfig->fbdefaultpage)
	{
		case 'recent':
			$func = 'latest';
			break;
		case 'my':
			$func = $my->id > 0 ? 'mylatest' : 'latest';
			break;
		default:
			$func = 'listcat';
	}
}

// Include the Community Builder language file if necessary and set CB itemid value
$cbitemid = 0;

if ($fbConfig->fb_profile == 'cb')
{
    // Include CB language files
    $UElanguagePath = KUNENA_ROOT_PATH .DS. 'components/com_comprofiler/plugin/language';
    $UElanguage = $lang;

    if (!file_exists($UElanguagePath .DS . $lang .DS . $lang . '.php')) {
        $UElanguage = 'default_language';
        }

    include_once ($UElanguagePath .DS . $UElanguage .DS . $UElanguage . '.php');
}

// Kunena Current Template Icons Pack
// See if there's an icon pack installed
$useIcons = 0; //init
$fbIcons = 0;

if (file_exists(KUNENA_ABSTMPLTPATH . '/icons.php'))
{
    include_once (KUNENA_ABSTMPLTPATH . '/icons.php');
    $useIcons = 1;
}
else
{
    include_once (KUNENA_PATH_TEMPLATE_DEFAULT .DS.  'icons.php');
}

// Check if we only allow registered users
if ($fbConfig->regonly && !$my->id)
{
    echo _FORUM_UNAUTHORIZIED . "<br />";
    echo _FORUM_UNAUTHORIZIED2;
}
// or if the board is offline
else if ($fbConfig->board_offline && !$is_admin)
{
    echo stripslashes($fbConfig->offline_message);
}
else
{
//
// This is the main session handling section. We rely both on cookie as well as our own
// Kunena session table inside the database. We are leveraging the cookie to keep track
// of an individual session and its various refreshes. As we will never know what the last
// pageview of a session will be (as defined by a commonly used 30min break/pause) we
// keep updateing the cookie until we detect a 30+min break. That break tells us to reset
// the last visit timestamp inside the database.
// We also redo the security checks with every new session to minimize the risk of exposed
// access rights though someone 'leeching' on to another session. This resets the cached
// priviliges after every 30 min of inactivity
//
	// We only do the session handling for registered users
	// No point in keeping track of whats new for guests
	if ($my->id > 0)
	{
		// First we drop an updated cookie, good for 1 year
		// We have consolidated multiple instances of cookie management into this single location
		// NOT SURE IF WE STILL NEED THIS ONE after session management got dbtized
		setcookie("fboard_settings[member_id]", $my->id, time() + KUNENA_SECONDS_IN_YEAR, '/');

		// We assume that this is a new user and that we don't know about a previous visit
		$new_fb_user = 0;
		$resetView = 0;

		// Lookup existing session sored in db. If none exists this is a first time visitor
		$database->setQuery("SELECT * from #__fb_sessions where userid=" . $my->id);
		$fbSessionArray = $database->loadObjectList();
			check_dberror("Unable to load sessions.");
		$fbSession = $fbSessionArray[0];
		$fbSessionUpd = null;

		// If userid is empty/null no prior record did exist -> new session and first time around
		if ($fbSession->userid == "" ) {
			$new_fb_user = 1;
			$resetView = 1;
			// Init new sessions for first time user
			$fbSession->userid = $my->id;
			$fbSession->allowed = '';
			$fbSession->lasttime = $systime - KUNENA_SECONDS_IN_YEAR;  // show threads up to 1 year back as new
			$fbSession->readtopics = '';
			$fbSession->currvisit = $systime;
		}

		// detect fbsession timeout (default: after 30 minutes inactivity)
		$fbSessionTimeOut = ($fbSession->currvisit + $fbConfig->fbsessiontimeout) < $systime;

		// new indicator handling
		if ($markaction == "allread") {
			$fbSession->lasttime = $fbSessionUpd->lasttime = $systime;
			$fbSession->readtopics = $fbSessionUpd->readtopics = '';
		} elseif ($fbSessionTimeOut) {
			$fbSession->lasttime = $fbSessionUpd->lasttime = $fbSession->currvisit;
			$fbSession->readtopics = $fbSessionUpd->readtopics = '';
		}

		// get all accessaible forums if needed (eg on forum modification, new session)
		if (!$fbSession->allowed or $fbSession->allowed == 'na' or $fbSessionTimeOut) {
			$allow_forums = CKunenaTools::getAllowedForums($my->id, $aro_group->group_id, $acl);
			if (!$allow_forums) $allow_forums = '0';
			if ($allow_forums <> $fbSession->allowed)
				$fbSession->allowed = $fbSessionUpd->allowed = $allow_forums;
			unset($allow_forums);
		}

		// save fbsession
		if ($new_fb_user) {
			$database->insertObject('#__fb_sessions', $fbSession);
				check_dberror('Unable to insert new session record for user.');
		} else {
			$fbSessionUpd->userid = $fbSession->userid;
			$fbSession->currvisit = $fbSessionUpd->currvisit = $systime;
			$database->updateObject('#__fb_sessions', $fbSessionUpd, 'userid');
				check_dberror('Unable to update session record for user.');
		}
		unset($fbSessionUpd);

		if ($markaction == "allread") {
		        $mainframe->redirect( JURI::base() .htmlspecialchars_decode(JRoute::_(KUNENA_LIVEURLREL)), _GEN_ALL_MARKED);
		}

		// Now lets get the view type for the forum
		$database->setQuery("select view from #__fb_users where userid=$my->id");
		$prefview = $database->loadResult();
			check_dberror('Unable load default view type for user.');

		// If the prefferred view comes back empty this must be a new user
		// who does not yet have a Kunena profile -> lets create one
		if ($prefview == "")
		{
			$prefview = $fbConfig->default_view;

			$database->setQuery("SELECT count(*) FROM #__fb_users WHERE userid=$my->id");
			$userexists = $database->loadResult();
			check_dberror('Unable load default view type for user.');
			if (!$userexists)
			{
				// there's no profile; set userid and the default view type as preferred view type.
				$database->setQuery("insert into #__fb_users (userid,view,moderator) values ('$my->id','$prefview','$is_admin')");
				$database->query();
					check_dberror('Unable to create user profile.');
			}
		}
		// Only reset the view if we have determined above that we need to
		// Without that test the user would not be able to make intra session
		// view changes by clicking on the threaded vs flat view link
		if ($resetView == 1)
		{
    		setcookie("fboard_settings[current_view]", $prefview, time() + KUNENA_SECONDS_IN_YEAR, '/');
	    	$view = $prefview;
	    }

	    // Assign previous visit without user offset to variable for templates to decide
		// whether or not to use the NEW indicator on forums and posts
		$prevCheck = $fbSession->lasttime; // - KUNENA_OFFSET_USER; Don't use the user offset - it throws the NEW indicator off
	}
	else
	{
		// collect accessaible categories for guest user
		$database->setQuery("SELECT id FROM #__fb_categories WHERE pub_access=0 AND published=1");
		$fbSession->allowed =
			($arr_pubcats = $database->loadResultArray())?implode(',', $arr_pubcats):'';
			check_dberror('Unable load accessible categories for user.');

		// For guests we don't show new posts
		$prevCheck = $systime;
	}

	// no access to categories?
	if (!$fbSession->allowed) $fbSession->allowed = '0';

//Disabled threaded view option for Kunena
//    //Initial:: determining what kind of view to use... from profile, cookie or default settings.
//    //pseudo: if (no view is set and the cookie_view is not set)
//    if ($view == "" && $settings['current_view'] == "")
//    {
//        //pseudo: if there's no prefered type, use FB's default view otherwise use preferred view from profile
//        //and then set the cookie right
//        $view = $prefview == "" ? $fbConfig->default_view : $prefview;
//        setcookie("fboard_settings[current_view]", $view, time() + KUNENA_SECONDS_IN_YEAR, '/');
//    }
//    //pseudo: otherwise if (no view set but cookie isn't empty use view as set in cookie
//    else if ($view == "" && $settings['current_view'] != "")
//  	{
//        $view = $settings['current_view'];
//    }

    $view = "flat";

    //Get the max# of posts for any one user
    $database->setQuery("SELECT max(posts) from #__fb_users");
    $maxPosts = $database->loadResult();
    	check_dberror('Unable load max(posts) for user.');

    //Get the topics this user has already read this session from #__fb_sessions
    $readTopics=$fbSession->readtopics;
    $read_topics = explode(',', $readTopics);

    /*       _\|/_
             (o o)
     +----oOO-{_}-OOo--------------------------------+
     |    Until this section we have included the    |
     |   necessary files and gathered the required   |
     |     variables. Now let's start processing     |
     |                     them                      |
     +----------------------------------------------*/

    //Check if the catid requested is a parent category, because if it is
    //the only thing we can do with it is 'listcat' and nothing else
    if ($func == "showcat" || $func == "view" || $func == "post")
    {
        $database->setQuery("SELECT parent FROM #__fb_categories WHERE id=$catid");
	    $strCatParent = $database->loadResult();
			check_dberror('Unable to load categories.');

        if ($catid == '' || $strCatParent == 0)
    		{
            $func = 'listcat';
        }
    }

    switch ($func)
    {
        case 'view':
            $fbMenu = KUNENA_get_menu(KUNENA_CB_ITEMID, $fbConfig, $fbIcons, $my->id, 3, $view, $catid, $id, $thread);

            break;

        case 'showcat':
            //get number of pending messages
            $database->setQuery("SELECT count(*) FROM #__fb_messages WHERE catid=$catid and hold=1");
            $numPending = $database->loadResult();
            	check_dberror('Unable load pending messages.');

            $fbMenu = KUNENA_get_menu(KUNENA_CB_ITEMID, $fbConfig, $fbIcons, $my->id, 2, $view, $catid, $id, $thread, $is_Moderator, $numPending);
            break;

        default:
            $fbMenu = KUNENA_get_menu(KUNENA_CB_ITEMID, $fbConfig, $fbIcons, $my->id, 1);

            break;
    }

    // display header
    $KunenaTemplate->addVar('kunena-header', 'menu', $fbMenu);
    $KunenaTemplate->addVar('kunena-header', 'board_title', stripslashes($board_title));
    if (file_exists(KUNENA_JTEMPLATEPATH.'/css/kunena.forum.css')) {
   		$KunenaTemplate->addVar('kunena-header', 'css_path', KUNENA_JTEMPLATEURL . '/template/' . $fbConfig->template . '/kunena.forum.css');
    } else {
   	    $KunenaTemplate->addVar('kunena-header', 'css_path', KUNENA_DIRECTURL . '/template/' . $fbConfig->template . '/kunena.forum.css');
	}

    $KunenaTemplate->addVar('kunena-header', 'offline_message', stripslashes($fbConfig->board_offline) ? '<span id="fbOffline">' . _FORUM_IS_OFFLINE . '</span>' : '');
    $KunenaTemplate->addVar('kunena-header', 'searchbox', getSearchBox());
    $KunenaTemplate->addVar('kunena-header', 'pb_imgswitchurl', KUNENA_URLIMAGESPATH . "shrink.gif");
    $KunenaTemplate->displayParsedTemplate('kunena-header');

    //BEGIN: PROFILEBOX
    if (file_exists(KUNENA_ABSTMPLTPATH . '/plugin/profilebox/profilebox.php')) {
        include (KUNENA_ABSTMPLTPATH . '/plugin/profilebox/profilebox.php');
        }
    else {
        include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/profilebox/profilebox.php');
        }
    //FINISH: PROFILEBOX

    switch ($func)
    {
        case 'who':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/plugin/who/who.php')) {
                include (KUNENA_ABSTMPLTPATH . '/plugin/who/who.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/who/who.php');
                }

            break;

        #########################################################################################
        case 'announcement':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/plugin/announcement/announcement.php')) {
                include (KUNENA_ABSTMPLTPATH . '/plugin/announcement/announcement.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/announcement/announcement.php');
                }

            break;

        #########################################################################################
        case 'stats':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/plugin/stats/stats.class.php')) {
                include (KUNENA_ABSTMPLTPATH . '/plugin/stats/stats.class.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/stats/stats.class.php');
                }

            if (file_exists(KUNENA_ABSTMPLTPATH . '/plugin/stats/stats.php')) {
                include (KUNENA_ABSTMPLTPATH . '/plugin/stats/stats.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/stats/stats.php');
                }

            break;

        #########################################################################################
        case 'fbprofile':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/plugin/fbprofile/fbprofile.php')) {
                include (KUNENA_ABSTMPLTPATH . '/plugin/fbprofile/fbprofile.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/fbprofile/fbprofile.php');
                }

            break;

        #########################################################################################
        case 'userlist':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/plugin/userlist/userlist.php')) {
                include (KUNENA_ABSTMPLTPATH . '/plugin/userlist/userlist.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/userlist/userlist.php');
                }

            break;

        #########################################################################################
        case 'post':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/smile.class.php')) {
                include (KUNENA_ABSTMPLTPATH . '/smile.class.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'smile.class.php');
                }

            if (file_exists(KUNENA_ABSTMPLTPATH . '/post.php')) {
                include (KUNENA_ABSTMPLTPATH . '/post.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'post.php');
                }

            break;

        #########################################################################################
        case 'view':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/smile.class.php')) {
                include (KUNENA_ABSTMPLTPATH . '/smile.class.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'smile.class.php');
                }

            if (file_exists(KUNENA_ABSTMPLTPATH . '/view.php')) {
                include (KUNENA_ABSTMPLTPATH . '/view.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'view.php');
                }

            break;

        #########################################################################################
        case 'faq':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/faq.php')) {
                include (KUNENA_ABSTMPLTPATH . '/faq.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'faq.php');
                }

            break;

        #########################################################################################
        case 'showcat':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/smile.class.php')) {
                include (KUNENA_ABSTMPLTPATH . '/smile.class.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'smile.class.php');
                }

            if (file_exists(KUNENA_ABSTMPLTPATH . '/showcat.php')) {
                include (KUNENA_ABSTMPLTPATH . '/showcat.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'showcat.php');
                }

            break;

        #########################################################################################
        case 'listcat':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/listcat.php')) {
                include (KUNENA_ABSTMPLTPATH . '/listcat.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'listcat.php');
                }

            break;

        #########################################################################################
        case 'review':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/smile.class.php')) {
                include (KUNENA_ABSTMPLTPATH . '/smile.class.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'smile.class.php');
                }

            if (file_exists(KUNENA_ABSTMPLTPATH . '/moderate_messages.php')) {
                include (KUNENA_ABSTMPLTPATH . '/moderate_messages.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'moderate_messages.php');
                }

            break;

        #########################################################################################
        case 'rules':
            include (KUNENA_PATH_LIB .DS. 'kunena.rules.php');

            break;

        #########################################################################################

        case 'userprofile':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/smile.class.php')) {
                include (KUNENA_ABSTMPLTPATH . '/smile.class.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'smile.class.php');
                }

            if (file_exists(KUNENA_ABSTMPLTPATH . '/plugin/myprofile/myprofile.php')) {
                include (KUNENA_ABSTMPLTPATH . '/plugin/myprofile/myprofile.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/myprofile/myprofile.php');
                }

            break;

        #########################################################################################
        case 'myprofile':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/smile.class.php')) {
                include (KUNENA_ABSTMPLTPATH . '/smile.class.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'smile.class.php');
                }

            if (file_exists(KUNENA_ABSTMPLTPATH . '/plugin/myprofile/myprofile.php')) {
                include (KUNENA_ABSTMPLTPATH . '/plugin/myprofile/myprofile.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/myprofile/myprofile.php');
                }

            break;

        #########################################################################################
        case 'report':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/plugin/report/report.php')) {
                include (KUNENA_ABSTMPLTPATH . '/plugin/report/report.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/report/report.php');
                }

            break;

        #########################################################################################
        case 'uploadavatar':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/plugin/myprofile/myprofile_avatar_upload.php')) {
                include (KUNENA_ABSTMPLTPATH . '/plugin/myprofile/myprofile_avatar_upload.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/myprofile/myprofile_avatar_upload.php');
                }

            break;

        #########################################################################################
        case 'latest':
        case 'mylatest':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/latestx.php')) {
                include (KUNENA_ABSTMPLTPATH . '/latestx.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'latestx.php');
                }

            break;

        #########################################################################################
        case 'search':
            require_once (KUNENA_PATH_LIB .DS. 'kunena.search.class.php');

            $searchword = JRequest::getVar('searchword', '');

            $KunenaSearch = &new CKunenaSearch($database, $searchword, $my->id, $limitstart, $fbConfig->messages_per_page_search);
            $KunenaSearch->show();
            break;

        //needs work ... still in progress
        case 'advsearch':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/plugin/advancedsearch/advsearch.php')) {
                include (KUNENA_ABSTMPLTPATH . '/plugin/advancedsearch/advsearch.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/advancedsearch/advsearch.php');
                }

            break;

        case 'advsearchresult':
            if (file_exists(KUNENA_ABSTMPLTPATH . '/plugin/advancedsearch/advsearchresult.php')) {
                include (KUNENA_ABSTMPLTPATH . '/plugin/advancedsearch/advsearchresult.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'plugin/advancedsearch/advsearchresult.php');
                }

            break;

        #########################################################################################
        case 'markthisread':
            // get all already read topics
            $database->setQuery("SELECT readtopics FROM #__fb_sessions WHERE userid=$my->id");
            $allreadyRead = $database->loadResult();
            	check_dberror("Unable to load read topics.");
            /* Mark all these topics read */
            $database->setQuery("SELECT thread FROM #__fb_messages WHERE catid=$catid and thread not in ('$allreadyRead') GROUP BY THREAD");
            $readForum = $database->loadObjectList();
            	check_dberror("Unable to load messages.");
            $readTopics = '--';

            foreach ($readForum as $rf) {
                $readTopics = $readTopics . ',' . $rf->thread;
                }

            $readTopics = str_replace('--,', '', $readTopics);

            if ($allreadyRead != "") {
                $readTopics = $readTopics . ',' . $allreadyRead;
                }

            $database->setQuery("UPDATE #__fb_sessions set readtopics='$readTopics' WHERE userid=$my->id");
            $database->query();
            	check_dberror('Unable to update readtopics in session table.');

            $mainframe->redirect( JURI::base() .htmlspecialchars_decode(JRoute::_(KUNENA_LIVEURLREL.'&amp;func=showcat&amp;catid='.$catid)), _GEN_FORUM_MARKED);
            break;

        #########################################################################################
        case 'karma':
            include (KUNENA_PATH_LIB .DS. 'kunena.karma.php');

            break;

        #########################################################################################
        case 'bulkactions':
            switch ($do)
            {
                case "bulkDel":
                    CKunenaTools::fbDeletePosts( $is_Moderator, $return);

                    break;

                case "bulkMove":
                    CKunenaTools::fbMovePosts($catid, $is_Moderator, $return);
                    break;
            }

            break;

        ######################

        /*    template chooser    */
        case "templatechooser":
            $fb_user_template = strval(JRequest::getVar('fb_user_template', '','COOKIE'));

            $fb_user_img_template = strval(JRequest::getVar('fb_user_img_template', $fb_user_img_template));
            $fb_change_template = strval(JRequest::getVar('fb_change_template', $fb_user_template));
            $fb_change_img_template = strval(JRequest::getVar('fb_change_img_template', $fb_user_img_template));

            if ($fb_change_template)
            {
                // clean template name
                $fb_change_template = preg_replace('#\W#', '', $fb_change_template);

                if (strlen($fb_change_template) >= 40) {
                    $fb_change_template = substr($fb_change_template, 0, 39);
                    }

                // check that template exists in case it was deleted
                if (file_exists(KUNENA_PATH_TEMPLATE .DS. $fb_change_template . '/kunena.forum.css'))
                {
                    $lifetime = 60 * 10;
                    $fb_current_template = $fb_change_template;
                    setcookie('fb_user_template', "$fb_change_template", time() + $lifetime);
                }
                else {
                    setcookie('fb_user_template', '', time() - 3600);
                    }
            }

            if ($fb_change_img_template)
            {
                // clean template name
                $fb_change_img_template = preg_replace('#\W#', '', $fb_change_img_template);

                if (strlen($fb_change_img_template) >= 40) {
                    $fb_change_img_template = substr($fb_change_img_template, 0, 39);
                    }

                // check that template exists in case it was deleted
                if (file_exists(KUNENA_PATH_TEMPLATE .DS. $fb_change_img_template . '/kunena.forum.css'))
                {
                    $lifetime = 60 * 10;
                    $fb_current_img_template = $fb_change_img_template;
                    setcookie('fb_user_img_template', "$fb_change_img_template", time() + $lifetime);
                }
                else {
                    setcookie('fb_user_img_template', '', time() - 3600);
                    }
            }

            $mainframe->redirect( JURI::base() .htmlspecialchars_decode(JRoute::_(KUNENA_LIVEURLREL)));
            break;

        #########################################################################################
        case 'credits':
            include (KUNENA_PATH_LIB .DS. 'kunena.credits.php');

            break;

        #########################################################################################
        default:
            if (file_exists(KUNENA_ABSTMPLTPATH . '/listcat.php')) {
                include (KUNENA_ABSTMPLTPATH . '/listcat.php');
                }
            else {
                include (KUNENA_PATH_TEMPLATE_DEFAULT .DS. 'listcat.php');
                }

            break;
    } //hctiws

    // Bottom Module
?>

<jdoc:exists type="modules" condition="kunena_bottom" />
        <div class = "bof-bottom-modul">
		<jdoc:include type="modules" name="kunena_bottom" />
        </div>
</jdoc:exists>
<?php
    // Credits
    echo '<div class="fb_credits"> ' . CKunenaLink::GetTeamCreditsLink($catid, _KUNENA_POWEREDBY) . ' ' . CKunenaLink::GetCreditsLink();
    if ($fbConfig->enablerss)
    {
    	$document->addCustomTag('<link rel="alternate" type="application/rss+xml" title="'._LISTCAT_RSS.'" href="'.JRoute::_(KUNENA_LIVEURLREL.'&amp;func=fb_rss&amp;no_html=1').'" />');
        echo CKunenaLink::GetRSSLink('<img class="rsslink" src="' . KUNENA_URLEMOTIONSPATH . 'rss.gif" border="0" alt="' . _LISTCAT_RSS . '" title="' . _LISTCAT_RSS . '" />');
    }
    echo '</div>';

    // display footer
    $KunenaTemplate->displayParsedTemplate('kunena-footer');
} //else

// Just for debugging and performance analysis
$mtime = explode(" ", microtime());
$tend = $mtime[1] + $mtime[0];
$tpassed = ($tend - $tstart);
//echo $tpassed;
?>
