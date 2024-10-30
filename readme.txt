=== knspr-cities ===
Contributors: Philipp Waldhauer
Tags: map, google maps, cities
Requires at least: 3.0
Tested up to: 3.1a
Stable tag: 1.0

This is a plugin to preset cities you've been to.

== Description ==

A plugin that uses a Google Maps map to present all the cities where you've been.

== Installation ==

1. Upload the plugin folder to your `/wp-content/plugins` directory
2. Get a Google Maps API key at http://code.google.com/intl/de/apis/maps/signup.html
3. Create a new page/post or edit an existing one and add:

		[knsprCities api="YOUR_API_KEY" div="THE_ID_OF_THE_DIV"]
		City 1,Comment 1
		City 2,Comment 2
		City 3
		City 4
		[/knsprCities]
	
		<div id="map" style="width: 500px; heigh: 500px"></div>

4. Look on the page. Refresh the page until all cities are geocoded.

== Changelog ==

= 1.0 =
 * Initial release



