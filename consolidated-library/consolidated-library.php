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
	if ($platform == "all" || $platform == "steam")
		updateSteamGames();

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
		videogames_print_list($results);
	else if ( $outputstyle == "grid")
		videogames_print_grid($results);
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
       echo "<p><b>Game List</b><br />";
       foreach($listresultset as $game){
           $title = $game['title'];
           echo "Title: $title<br />";
       }
       
       echo "</p>";
}

function videogames_print_grid($resultset) {
       	echo "<p><b>Game Grid</b><br />";
		echo "<table><thead><th></th><th>Title</th><th>Platform</th><th>Genre(s)</th><th>Coop</th><th>Rating</th><th>Notes</th></thead>";
       	foreach($resultset as $game){
			echo "<tr>";
			echo "<td>";
			if ($game['graphic_url'] != "")
				echo "<img src=\"" . $game['graphic_url'] . "\">";
			echo "</td>";
			if ($game['amazon_link'] != "")
				$link = "<a href=\"http://www.amazon.com/gp/product/" . $game['amazon_link'] . "/ref=as_li_ss_tl?ie=UTF8&camp=1789&creative=390957&creativeASIN=" . $game['amazon_link'] . "&linkCode=as2&tag=arnfamtim-20\">" . $game['title'] . "</a>";
			else
				$link = $game['title'];
			echo "<td>" . $link . "</td>";
			echo "<td>" . $game['platform'] . "</td>";
			echo "<td>" . $game['genre'] . "</td>";
			if ($game['coop'] == 1)
				$coop = 'Y';
			else
				$coop = 'N';
			echo "<td>" . $coop . "</td>";
			echo "<td>" . $game['rating'] . "</td>";
			echo "<td>" . $game['notes'] . "</td>";
			echo "</tr>";
       	}
       	echo "</table>";
       	echo "</p>";
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
	$steamid = get_option('consol_lib_steam_name');
	$community_url = "http://steamcommunity.com/";
	$api_format = "?xml=1";
	$profile_path = (is_numeric($steamid)) ? "profiles/$steamid/" : "id/$steamid/";
	$games_xml_url = $community_url.$profile_path.'games'.$api_format;
	$games = get_games($games_xml_url);

	if(array_key_exists('error',$games)) {
		echo $games['error'];
	}
	else {
	       	echo "<p><b>Updating Steam Games</b><br />";
	global $wpdb;
	$numberGamesAdded = 0;
		foreach ($games as $key => $value) {
			$gameAppID = $games[$key]->appID;
			$gameTitle = $games[$key]->name;
			$gameLogo = $games[$key]->logo;
			$gameNotes = "Hours on Record: " . $games[$key]->hoursOnRecord;

			$notExists = " where not exists (select steam_appid from wp_consol_lib_videogames where steam_appid = $gameAppID)";
			$steamInsertQuery = $wpdb->prepare("insert into wp_consol_lib_videogames (platform,steam_appid,title,graphic_url,notes) select * from (select 'steam',%d,%s,%s,%s) as vg $notExists",$gameAppID,$gameTitle,$gameLogo,$gameNotes);
			$numRowsInserted = $wpdb->query($steamInsertQuery);
			$numberGamesAdded += $numRowsInserted;
		}
		echo "Number of Steam Games Added: <b>$numberGamesAdded</b>";
       	echo "</p>";
	}
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

?>