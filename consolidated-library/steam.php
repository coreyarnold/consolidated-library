<?php
$steamid = 'coreyarnold';
$community_url = "http://steamcommunity.com/";
$api_format = "?xml=1";
$profile_path = (is_numeric($steamid)) ? "profiles/$steamid/" : "id/$steamid/";
$games_xml_url = $community_url.$profile_path.'games'.$api_format;
$games = get_games($games_xml_url);

//print_r($games);
if(array_key_exists('error',$games)) {
	echo $games['error'];
}
else {
	foreach ($games as $key => $value) {
		echo $games[$key]->logo . '<br />';
		echo $games[$key]->name . '<br />';
		if (property_exists($games[$key],'hoursOnRecord')) {
			echo "hours: " . $games[$key]->hoursOnRecord . "<br />";
		}
		
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

?>