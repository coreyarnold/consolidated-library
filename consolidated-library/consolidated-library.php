	<?php
/*
Plugin Name: Consolidated Library
Version: 0.3
Plugin URI: http://www.coreyarnold.com/ConsolidatedLibrary/
Description: Provides a consolidated place to store libraries of things. DVDs, Books, Video Games, Board and Card Games, CDs, etc.
Author: Corey Arnold
Author URI: http://www.coreyarnold.com/
*/

    register_activation_hook( __FILE__, 'consol_install');

    function consol_install(){
        global $wpdb;
        $tablename = $wpdb->prefix . "consol_lib_videogames";
        $sql = "CREATE TABLE `$tablename` (
        `id` mediumint(9) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `platform` varchar(100) DEFAULT NULL,
        `genre` varchar(100) DEFAULT NULL,
        `singleplayer` tinyint(1) DEFAULT NULL,
        `multiplayer` tinyint(1) DEFAULT NULL,
        `multiplayerMaxPlayers` decimal(1,0) DEFAULT NULL,
        `coop` tinyint(1) DEFAULT 0,
        `coopMaxPlayers` decimal(1,0) DEFAULT NULL,
        `rating` decimal(2,1) DEFAULT NULL,
        `purchase_price` decimal(5,2) DEFAULT NULL,
        `purchase_date` date DEFAULT NULL,
        `amazon_link` varchar(255) DEFAULT NULL,
		`steam_appid` mediumint(9) DEFAULT NULL,
        `notes` varchar(1000) DEFAULT NULL,
        `status` enum ('Own it','Wishlist'),
		`play_state` enum ('not started','not playing', 'playing', 'completed'),
		`graphic_url` varchar(255) DEFAULT NULL,
        `lastupdated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
		) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
		";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);        

        $tablename = $wpdb->prefix . "consol_lib_steamfriends";
        $sql = "CREATE TABLE `$tablename` (
        `id` int(9) NOT NULL AUTO_INCREMENT,
        `steamid` bigint(20) NOT NULL,
		`PersonaName` varchar(100) DEFAULT NULL,
        `RealName` varchar(100) DEFAULT NULL,
		`AvatarURL` varchar(255) DEFAULT NULL,
		`SteamVisibilityState` int(2) DEFAULT NULL,
        `lastupdated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
		) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
		";
		
		dbDelta($sql);        
    }
    
// [videogames outputstyle="grid/list" platform="xbox360/ps3/steam/pc/mac" status="" rating="" coop=""]
   add_shortcode( 'videogames', 'consol_lib_printvideogames');
function consol_lib_printvideogames($atts) {
	extract( shortcode_atts( array(
	    'outputstyle' => "list",
		'platform' => "all",
		'status' => "all",
		'rating' => "all",
		'coop' => "all",
	), $atts ) );
	if ($platform == "all" || $platform == "steam"){
		updateSteamGames();
	}

       global $wpdb;
//        $wpdb->show_errors();
       $tablename = $wpdb->prefix . "consol_lib_videogames";

	$filters = "";
	$filters = consol_lib_addFilter($filters,"platform","%s",$platform);
	$filters = consol_lib_addFilter($filters,"status","%s",$status);
	$filters = consol_lib_addFilter($filters,"rating","%d",$rating);
	$filters = consol_lib_addFilter($filters,"coop","%s",$coop);

	if ($filters != "")
		$filters = "WHERE $filters";
       $sql = "select id, title, platform, genre, coop, coopMaxPlayers, rating, amazon_link, graphic_url,notes from $tablename $filters order by title;";

       $results = $wpdb->get_results($sql, ARRAY_A);
	if ($outputstyle == "list")
		return videogames_print_list($results);
	else if ( $outputstyle == "grid")
		return videogames_print_grid($results);
}

function consol_lib_addFilter($filterstring,$column,$type,$value){
	global $wpdb;
	if ($value == "all") {
		return $filterstring;
	}
	else {
		if ($filterstring != "")
			$filterstring .= " AND ";
		$filterstring .= $wpdb->prepare("$column = $type",$value);
		return $filterstring;	
	}
}

function videogames_print_list($listresultset) {
       $returnString = "";
       $returnString .= "<p><b>Game List</b><br />";
       foreach($listresultset as $game){
           $title = $game['title'];
           $returnString .= "Title: $title<br />";
       }
       
       $returnString .= "</p>";
}

function videogames_print_grid($resultset) {
       	$returnString .= "<p><b>Game Grid</b><br />";
		$returnString .= "<table><thead><th></th><th>Title</th><th>Platform</th><th>Genre(s)</th><th>Coop</th><th>Rating</th><th>Notes</th></thead>";
       	foreach($resultset as $game){
			$returnString .= "<tr>";
			$returnString .= "<td>";
			if ($game['graphic_url'] != "")
				$returnString .= "<img src=\"" . $game['graphic_url'] . "\">";
			$returnString .= "</td>";
			if ($game['amazon_link'] != "")
				$link = "<a href=\"http://www.amazon.com/gp/product/" . $game['amazon_link'] . "/ref=as_li_ss_tl?ie=UTF8&camp=1789&creative=390957&creativeASIN=" . $game['amazon_link'] . "&linkCode=as2&tag=arnfamtim-20\">" . $game['title'] . "</a>";
			else
				$link = $game['title'];
			$returnString .= "<td>" . $link . "</td>";
			$returnString .= "<td>" . $game['platform'] . "</td>";
			$returnString .= "<td>" . $game['genre'] . "</td>";
			if ($game['coop'] == 1)
				$coop = 'Y';
			else
				$coop = 'N';
			$returnString .= "<td>" . $coop . "</td>";
			$returnString .= "<td>" . $game['rating'] . "</td>";
			$returnString .= "<td>" . $game['notes'] . "</td>";
			$returnString .= "</tr>";
       	}
       	$returnString .= "</table>";
       	$returnString .= "</p>";

        return $returnString;
}

//   add_shortcode( 'steamgames', 'updateSteamGames');

//*************** Admin function ***************
function consol_lib_admin() {
	include('consol_lib_admin.php');
}

function consol_lib_admin_actions() {
//    add_options_page("Consolidated Library Display", "Consolidated Library Display", 'administrator', "Consolidated Library Display", "consol_lib_admin");
	add_options_page('My First', 'Consolidated Library', 'administrator', 'consol-library', 'consol_lib_admin');
}

add_action('admin_menu', 'consol_lib_admin_actions');


/************************************************/

function updateSteamGames()
{
	$returnString = "";
	$steamid = get_option('consol_lib_steam_name');
	$community_url = "http://steamcommunity.com/";
	$api_format = "?xml=1";
	$profile_path = (is_numeric($steamid)) ? "profiles/$steamid/" : "id/$steamid/";
	$games_xml_url = $community_url.$profile_path.'games'.$api_format;
	$games = get_games($games_xml_url);

	if(array_key_exists('error',$games)) {
		$returnString .= $games['error'];
	}
	else {
	       	$returnString .= "<p><b>Updating Steam Games</b><br />";
	global $wpdb;
	$numberGamesAdded = 0;
		foreach ($games as $key => $value) {
			$gameAppID = $games[$key]->appID;
			$gameTitle = $games[$key]->name;
			$smallthumbgraphic = "http://cdn.steampowered.com/v/gfx/apps/".$games[$key]->appID."/capsule_sm_120.jpg";
			$gameLogo = $games[$key]->logo;
			$gameNotes = "Hours on Record: " . $games[$key]->hoursOnRecord;

			$notExists = " where not exists (select steam_appid from wp_consol_lib_videogames where steam_appid = $gameAppID)";
			$steamInsertQuery = $wpdb->prepare("insert into wp_consol_lib_videogames (platform,steam_appid,title,graphic_url,notes) select * from (select 'steam',%d,%s,%s,%s) as vg $notExists",$gameAppID,$gameTitle,$smallthumbgraphic,$gameNotes);
			$numRowsInserted = $wpdb->query($steamInsertQuery);
			$numberGamesAdded += $numRowsInserted;
		}
		$returnString .= "Number of Steam Games Added: <b>$numberGamesAdded</b>";
       	$returnString .= "</p>";
	}
	
	return $returnString;
}

	function get_games($games_xml_url) {
		$games = array();
		/** @var \stdClass $xml_object */
		$xml_object = get_games_xml_as_obj($games_xml_url);
		if (check_if_user_has_no_games($xml_object)) {
			$games['error'] = 'no games';
			return $games;
		}
		if (check_if_users_profile_is_private($xml_object)) {
			$games['error'] = 'private_user_profile';
			return $games;
		}
		if (isset($xml_object->games->game)) {
			$games = create_games_array($xml_object->games->game);
		}
		return $games;
	}

	/**
	 * SimpleXMLElement is a resource, not an object so we'll use a
	 * hack to make it into a useable object by encoding and decoding
	 * it to and from JSON.
	 *
	 * @return	bool|\stdClass
	 */
	function get_xml_as_obj($games_xml_url) {
		$xml_response = get_xml($games_xml_url);
		if ($xml_response == false) {
			return false;
		}
		$xml_object = convert_to_object($xml_response);
		return $xml_object;
	}

	function get_xml($games_xml_url) {
		$location = $games_xml_url;
		$xml_response = @simplexml_load_file($location, null, LIBXML_NOCDATA);
		if ($xml_response == false) {
			return false;
		}
		return $xml_response;
	}

	function get_games_xml_as_obj($games_xml_url) {
		return get_xml_as_obj($games_xml_url);
	}

	function convert_to_object($simplexml_object) {
		return json_decode(json_encode($simplexml_object));
	}

	function check_if_users_profile_is_private($xml_object) {
		if (isset($xml_object->error) && preg_match('/private/', $xml_object->error)) {
			return true;
		} else {
			return false;
		}
	}

	function check_if_user_has_no_games($xml_object) {
		if (isset($xml_object) && isset($xml_object->games) && !isset($xml_object->games->game)) {
			return true;
		}
		return false;
	}

	function create_games_array($games) {
		$games_array = array();
		if (is_array($games) && count($games) > 0) {
			foreach ($games as $game) {
				$games_array[$game->appID] = $game;
			}
		} elseif (is_object($games)) {
			if (isset($games->appID)) {
				$games_array[$games->appID] = $games;
			}
		}
		return $games_array;
	}
/************************************************/
add_shortcode( 'steamfriends', 'steamfriends');
function steamfriends(){
    updateSteamFriends();
	return showSteamFriends();
}

function updateSteamFriends(){
	findNewSteamFriends();
	updateSteamFriendsData();
}

function findNewSteamFriends(){
	$steamAPIKey = get_option('consol_lib_steam_api_key'); //"D5B5B67015EDEA4B8C6D356445F30BEE";
	$mySteamID = "76561198039887430";
	$friendsURL = "http://api.steampowered.com/ISteamUser/GetFriendList/v0001/?key=" . $steamAPIKey . "&steamid=" . $mySteamID . "&relationship=friend";
	$xmlurl = $friendsURL."&format=xml";

	$jsonurl = $friendsURL."&format=json";
	$json = url_get_contents($jsonurl) ;//file_get_contents($jsonurl,0,null,null);
	$json_output = json_decode($json);

	global $wpdb;
	foreach ( $json_output->friendslist->friends as $friend )
	{
        $tablename = $wpdb->prefix . "consol_lib_steamfriends";
		$sql = $wpdb->prepare("insert into $tablename(steamid) select * from (select %d) as f where not exists(select steamid from $tablename where steamid = %d);",$friend->steamid, $friend->steamid);
		$wpdb->query($sql);
	}
}
function updateSteamFriendsData(){
$return = "<p>";
	// loop through friends in steamfriends table and update the following
	// `PersonaName` varchar(100) DEFAULT NULL,
    // `RealName` varchar(100) DEFAULT NULL,
	// `SteamVisibilityState` int(2) DEFAULT NULL,
	global $wpdb;
	$tablename = $wpdb->prefix . "consol_lib_steamfriends";
	$sql = $wpdb->prepare("select id,steamid,personaname,realname,steamvisibilitystate from $tablename;");

	$results = $wpdb->get_results($sql, ARRAY_A);
$friendSteamIDs = "";
	foreach ($results as $friend) {
$return .= $friend['steamid']."<br />";
		if ($friendSteamIDs != "")
			$friendSteamIDs .= ",";
		$friendSteamIDs .= $friend['steamid'];
	}
$return .= $friendSteamIDs."<br />";

	$steamAPIKey = get_option('consol_lib_steam_api_key'); //"D5B5B67015EDEA4B8C6D356445F30BEE";
//				   http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=D5B5B67015EDEA4B8C6D356445F30BEE&steamids=76561197960435530
	$profileURL = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $steamAPIKey . "&steamids=" . $friendSteamIDs;

	$jsonurl = $profileURL."&format=json";
	$json = url_get_contents($jsonurl); //file_get_contents($jsonurl,0,null,null);
	$json_output = json_decode($json);
	// so now we have a json object of response->players
	global $wpdb;
	foreach ( $json_output->response->players as $friend )
	{
$return .= "updating friend [$friend->personaname] data<br />";
	$PersonaUpdate = dataUpdateText('PersonaName',$friend->personaname,"%s");
	$PersonaFilter = notNullFilter('PersonaName',$friend->personaname,"%s");
	$RealNameUpdate = dataUpdateText('RealName',$friend->realname,"%s");
	$RealNameFilter = notNullFilter('RealName',$friend->realname,"%s");
	$updateSQL = "update $tablename set 
		$PersonaUpdate
		where steamid = $friend->steamid AND NOT (
			$PersonaFilter
		);";

	$result = $wpdb->query($updateSQL);
	if ($result === false)
$return .= "Error<br />";
	else if ($result == 0)
$return .= "$friend->personaname not updated. was already up to date<br />";
	else
$return .= "updated $friend->personaname<br />";
$return .= "</p>";
	}

//return $return;
}

function showSteamFriends(){
	global $wpdb;
	$tablename = $wpdb->prefix . "consol_lib_steamfriends";

	$sql = "select SteamID,PersonaName,RealName from $tablename order by PersonaName;";

	$results = $wpdb->get_results($sql, ARRAY_A);
	$outputString = "";

	foreach ($results as $friend) {
		$NickName = $friend['PersonaName'];
		$RealName = "";
		if (!is_null($friend['RealName']))
			$RealName = $friend['RealName'];
		$outputString .= "Friend: $NickName <br />";
	}
	return $outputString;
}

function dataUpdateText($column,$value,$type){
	if ($value == "")
		return "";
	global $wpdb;
	return "$column = " . $wpdb->prepare("$type",$value);
}
function notNullFilter($column,$value,$type){
	if ($value == "")
		return "";
	global $wpdb;
	return "$column IS NOT NULL AND $column = " . $wpdb->prepare("$type",$value);
}
function url_get_contents ($Url) {
    if (!function_exists('curl_init')){ 
        die('CURL is not installed!');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $Url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
//	CURLOPT_HTTPHEADER => array('Content-type: application/json') ,
//	curl_setopt($ch, CURLOPT_POSTFIELDS);
//	CURLOPT_POSTFIELDS => $json_string
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}
?>