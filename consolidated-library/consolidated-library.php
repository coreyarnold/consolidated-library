<?php
/*
Plugin Name: Consolidated Library
Version: 0.2
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

       global $wpdb;
//        $wpdb->show_errors();
       $tablename = $wpdb->prefix . "consol_lib_videogames";

	$filters = "";
	if ($platform != "all")
	{
		if ($filters != "")
			$filters .= " AND ";
		$filters .= $wpdb->prepare("platform = %s",$platform);
	}
	if ($status != "all")
	{
		if ($filters != "")
			$filters .= " AND ";
		$filters .= $wpdb->prepare("status = %s",$status);
	}	
	if ($rating != "all")
	{
		if ($filters != "")
			$filters .= " AND ";
		$filters .= $wpdb->prepare("rating >= %d",$rating);
	}
	if ($coop != "all")
	{
		if ($filters != "")
			$filters .= " AND ";
		$filters .= $wpdb->prepare("coop = %s",$coop);
	}
	if ($filters != "")
		$filters = "WHERE $filters";
       $sql = "select id, title, platform, genre, coop, coopMaxPlayers, rating, amazon_link, graphic_url from $tablename $filters order by title;";

       $results = $wpdb->get_results($sql, ARRAY_A);
	if ($outputstyle == "list")
		videogames_print_list($results);
	else if ( $outputstyle == "grid")
		videogames_print_grid($results);
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
		echo "<table><thead><th></th><th>Title</th><th>Platform</th><th>Genre(s)</th><th>Coop</th><th>Rating</th></thead>";
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
			echo "</tr>";
       	}
       	echo "</table>";
       	echo "</p>";
}

   add_shortcode( 'steamgames', 'videogames_liststeamgames');
function videogames_liststeamgames(){
	///games?tab=all&xml=1
	$BaseSteamURL = "http://steamcommunity.com/id/" . get_option('steam_profile') . "/";
	$ProfileURL = $BaseSteamURL . "?xml=1";
	PrintSteamProfileInfo($ProfileURL);
	$GamesURL = $BaseSteamURL . 'games?tab=all&xml=1';
	echo "<a href=\"$GamesURL\">" . $GamesURL . "</a><br />";
	$xml = simplexml_load_file($ProfileURL);
	$steamID64 =  $xml->steamID64;
	parseSteamXML($xml);
//	$iter = new SimpleXMLIterator($xml,null);
}
/* start test code */

  function parseSteamXML($str) {
	var_dump($str);
  }

	function PrintSteamProfileInfo($pURL) {
		$profileXML = simplexml_load_file($pURL);
		echo "<div>";
		echo "SteamID: " . $profileXML->steamID . "<br />";
		echo $profileXML->stateMessage . "<br />";
		echo "</div>";
	}

/* end test code */
   /* Creates new database field */
   add_option("steam_profile", 'coreyarnold', '', 'yes');
   add_option("steam_api_key", '', '', 'yes');

/* Admin Panel */
if ( is_admin() ){
   add_action('admin_menu', 'my_first_admin_menu');

   function my_first_admin_menu() {
      add_options_page('My First', 'Consolidated Library', 'administrator', 'consol-library', 'consol_library_plugin_page');
   }
}

/* Call the Plugin Interface Page Code */
 function consol_library_plugin_page() {
 ?>
 <div>
 <h2>Consolidated Library Plugin Options Page</h2>

 <form method="post" action="options.php">
 <?php wp_nonce_field('update-options'); ?>

 <table width="510">
 <tr valign="top">
 <th width="92" scope="row">Steam Profile:</th>
 <td width="406">
 <input name="steam_profile" type="text" id="steam_profile" value="<?php echo get_option('steam_profile'); ?>" />
 </td>
 </tr>
 </table>

 <input type="hidden" name="action" value="update" />
 <input type="hidden" name="page_options" value="my_first_data" />

 <p>
 <input type="submit" value="<?php _e('Save Changes') ?>" />
 </p>

 </form>
 </div>
 <?php
}



?>