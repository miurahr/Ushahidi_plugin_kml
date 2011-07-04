<?php defined('SYSPATH') or die('No direct script access.');
// Basic configuration
$config['kmlsite'] = "http://www.sinsai.info/ushahidi/";
$config['default_limit'] = 1000;
// for google maps
	// max file size :     3MB
	// max raw KML size : 10MB
	// max network link : 10
	// max items        : 1000 <-- apply
	// max items in view: 80
    // --  in sinsai.info stat
    // 1000items  6.8MB in raw KML, 0.5MB in KMZ
    // 1500items  9.8MB in raw KML, 1.3MB in KMZ

// cache/cdn contorol
$config['cdn_kml_url'] = "http://cdn.sinsai.info.cache.yimg.jp/ushahidi/media/uploads/latest.kmz";
$config['cache_secs'] = 120;  // seconds during which to serve cached file, 
                            // after which re-generate on next request
$config['cache_on'] = true;   // true  = use cache file 
                            // false = debug mode: re-generated on each req.

// views options
$config['options'] = array(
	"upload_directory" => "http://www.sinsai.info/ushahidi/". "media/uploads/",
	'placemark_balloon_width' => "340",  // width in pixels (suggest 200 to 500)
	'document_balloon_width' => "340",  // width in pixels (suggest 200 to 500)
	'title_text_color' => "black",  // HTML color (works in Google Earth, ignored in Google Maps)
	'verified_text_color' => "green",  // HTML color (works in Google Earth & Maps)
	'description_text_color' => "black",  // HTML color (works in Google Earth & Maps)
	'location_text_color' => "gray",     // HTML color (works in Google Eatth & Maps)
	'categories_text_color' => "gray",  // HTML color (works in Google Earth & Maps)
	'date_text_color' => "gray",  // HTML color (works in Google Earth & Maps)
	'verified_in_balloons' => true,   // True/False, true = show verified status in balloons, false = don't show
	'media_in_balloons' => true,  // True/False, true = show media links (if available) in balloons, false = don't show
	'location_in_balloons' => true,   // True/False, true = show location in balloons, false = don't show
	'cats_in_balloons' => "icons",    // tree/icons/none,  tree = show categories tree listing in balloons, icons = only show category icons, none = don't show icons
	'cat_parents' => false,   // if showing cats_in_balloons, include parent categories in the tree or icon list
	'cat_icon_size' => 16,    // size in pixels of category icons in balloons

//--- Structure Options --
	'folder_snippets' => 0,  //  1/0, 1 = show category description as snippet (if description different from title), 0 = no snippet.
	'visibility' => 1,  //  1/0, 1 = placemarks & folders visible on initial load, 0 = not visible
	'extended_data' => true,      // True/False, true = write extended data section in each placemark kml, false = No extended data
	'bundle_icons' => true,   // True/False, true = bundle icon images into KMZ, false = link to images on server
	'bundle_media_tumbs' => false,    // True/False, true = bundle media thumbnail images into KMZ, false = link to images on server
	'normal_label_scale' => 0.8,
	'highlight_label_scale' => 0.9,
	'normal_icon_scale' => 0.6,
	'highlight_icon_scale' => 0.8,
	'compress' => true,
);

//=== Logo Details == (image file for in balloons: png/jpg/gif; suggested size: 36 x 36 pixels)
$config['logo'] = array(
	'path' => "http://www.sinsai.info/ushahidi/" . "plugins/kml/views/",
	'filename' => "sinsai_logo_36x36.png",
	'width' => 36,
	'height' => 36,
);

