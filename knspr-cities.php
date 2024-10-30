<?php
/*
Plugin Name: knspr-cities
Plugin URI: http://knuspermagier.de
Description: Shows cities
Author: Philipp Waldhauer
Version: 1.0
Author URI: http://knuspermagier.de
*/



class KnsprCity {
	public $lat;
	public $lng;
	public $name;
	public $lived_there = false;
	public $comment = '';
}

class KnsprCities {

	public static function loadCache() {
		global $post;

		$lats = get_post_meta($post->ID, 'citylatlong', true);
		if(!empty($lats)) {
			$lats = unserialize($lats);
		} else {
			$lats = array();
		}

		return $lats;
	}

	public static function saveCache(array $lats) {
		global $post;

		add_post_meta($post->ID, 'citylatlong', serialize($lats), true) or update_post_meta($post->ID, 'citylatlong', serialize($lats));
	}

	public static function geocode($addr) {
		$url = 'http://maps.google.com/maps/api/geocode/json?address='. urlencode($addr) .'&sensor=false';
		$json = json_decode(file_get_contents($url));

		if(!is_object($json) || $json->status != 'OK') {
			return array(null, null);
		}
	
		$first = $json->results[0]->geometry->location;
		return array($first->lat, $first->lng);
	}

	/**
	 * City name,Comment
 	 * If city is prefixed with a '!' it will be treated as
         * 'lived_there'.
        */
	public static function parseCities($content) {
		$lines = explode("\r\n", $content);
		$cities = array();

		$latlang = KnsprCities::loadCache();
		foreach($lines as $line) {
			$line = trim($line);

			if(empty($line)) {
				continue;
			}

			$city = new KnsprCity();
			if($line[0] == '!') {
				$city->lived_there = true;
				$line = substr($line, 1);
			}

			if(strpos($line, ',')) {
				$split = explode(',', $line);
				$city->name = $split[0];
				$city->comment = $split[1];
			} else {
				$city->name = $line;
			}

			/**
   			 * So, we have a name, now check the geo coords
			*/
			if(!isset($latlang[$city->name]) || empty($latlang[$city->name][0])) {
				$latlang[$city->name] = KnsprCities::geocode($city->name);
			}

			$city->lat = $latlang[$city->name][0];
			$city->lng = $latlang[$city->name][1];
		
			$cities[] = $city;
		}

		KnsprCities::saveCache($latlang);

		return $cities;
	}

	public static function printCities($api, $content, $div, $return = false) {
		$o = '';

		$o .= '<script src="http://maps.google.com/maps/api/js?v=3.1&amp;sensor=false&amp;key='. $api .'" type="text/javascript"></script>';
		$o .= '<script type="text/javascript">
			function initialize() {
					var map = new google.maps.Map(document.getElementById("'. $div .'"), {zoom: 6, center: new google.maps.LatLng(51.5, 9.1), mapTypeId: google.maps.MapTypeId.ROADMAP});

					var livedIcon = "http://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png";
					';


		$defects = array();		
		$cities = KnsprCities::parseCities($content);

		foreach($cities as $city) {
			if(empty($city->lat) || empty($city->lng)) {
				$defects[] = $city;
				continue;
			}

			$marker = 'marker_'. uniqid();

			$options = '{position: new google.maps.LatLng('. $city->lat .', '. $city->lng .'), title: "'. $city->name .'"';
			if($city->lived_there == true) {
				$options .= ', icon: livedIcon';
			}
			$options .= '}';
			
			
			$o .= 'var infoWindow = new google.maps.InfoWindow({content: ""});';
			$o .= 'var '. $marker .' = new google.maps.Marker('. $options .');';
			$o .= 'google.maps.event.addListener('. $marker .', "click", function() {
				infoWindow.content = "<strong>'. htmlspecialchars($city->name) .'</strong><br/>'. htmlspecialchars($city->comment) .'";
				infoWindow.open(map, '. $marker .');
			});

			'. $marker .'.setMap(map);';
		}


		$o .= '			

			}
	
			jQuery(document).ready(function() { initialize(); });
		      </script>';
			
		if(count($defects)) {
			$o .= '<p>Folgende Städte konnten nicht geocoded werden:</p><ul>';
			foreach($defects as $defect) {
				$o .= '<li>'. $defect->name .'</li>';
			}
			$o .= '</ul>';	
		}

		if($return) {
			return $o;
		}
		
		echo $o;
	}	

}

function knsprCitiesCallback($content) {
	$content = preg_replace_callback('/\[knsprCities api="(.*)" div="(.*)"\](.*)\[\/knsprCities\]/isU', 'knsprCitiesCallbackCallback', $content);

	return $content;
}

function knsprCitiesCallbackCallback($match) {
	$api = $match[1];
	$div = $match[2];
	$content = str_replace('<br />', "\r\n", $match[3]);

	return KnsprCities::printCities($api, $content, $div, true);
}

add_filter('the_content', 'knsprCitiesCallback');

?>
