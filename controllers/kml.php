<?php defined('SYSPATH') or die('No direct script access.');
/**
 * KML Controller
 * Generates KML with PlaceMarkers and Category Styles
 * organized by categories and subcategories
 * includes high quality balloon styling
 * includes icons from Ushahidi swatch generator, which work well in both Google Earth and Maps
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - http://source.ushahididev.com
 * @module	   Feed Controller	
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
* 
*/

class Kml_Controller extends Controller
{
	public function index()
	{
		// 1. get limit
		// 2. set filename
		// 3. ditect cache
		// 4.1. has cache -> return file
		// 4.2. no cache -> get data from sql, and write in view

		// 1.
		$limit = 0;
		if (isset($_GET['l']) AND !empty($_GET['l']))
		{
			$limit = (int) $_GET['l'];
		}

		// cron on?
		$cron_flag = false;
		if (isset($_GET['cron']) AND !empty($_GET['cron']))
		{
			$cron_flag = true;
			$limit = 0; // execute cron with no limit.
		}

        if ($limit == 0 && $cron_flag == false) {
            Kohana::config_load('kml');
            url::redirect(Kohana::config("kml.cdn_kml_url"));
        }

		// 2.
		$kml_filename = "latest.kml";  // filename for exported KML file
		$kmz_filename = "latest.kmz";  // filename for exported KMZ file

		if ($limit != 0) 
		{
			$kml_filename = $kml_filename . "_" . strval($limit);
			$kmz_filename = $kmz_filename . "_" . strval($limit);
		}

		$kmlFileName = Kohana::config('upload.directory', TRUE) . $kml_filename;  // internal path to KML file in uploads directory
		$kmzFileName = Kohana::config('upload.directory', TRUE)  . $kmz_filename;  // internal path to KMZ file in uploads directory

		// 3.

		//=== Caching Options ==
		$cache_secs = 60; 	// seconds during which to serve cached file, after which re-generate on next request
		$cache_on = true; 	// true = cache file, false = debug mode: file is re-generated on each request

		$use_cache = false;

		if (file_exists($kmzFileName) && (time() - filemtime($kmzFileName) < $cache_secs) && $cache_on) {
			$use_cache = true;
		}
		
		// 4.
		if ($use_cache)
		{
			// 4.1
			$incidents = NULL;
			$categories = NULL;
			
		}
		else
		{
			// 4.2
			if ($limit != 0) 
			{
				// If so, Get all incidents upto limit
				$incidents = ORM::factory('incident')
					->where('incident_active', '1')
					->orderby('incident_date', 'desc')
					->limit($limit)
					->find_all();
			}
			else
			{
				// Otherwise, Get all incidents (no limit)
				$incidents = ORM::factory('incident')
					->where('incident_active', '1')
					->orderby('incident_date', 'desc')
					->find_all();
			}
			// Get all Categories...
			$categories = ORM::factory('category')
				->where('category_visible', '1')
				->find_all();

		}
		
		//header("Content-Type: application/vnd.google-earth.kml+xml");
		header("Content-Type: application/vnd.google-earth.kmz");
		//header("Content-Disposition: attachment; filename=".time().".kml");
		//header("Content-Disposition: attachment; filename=ushahidi_link.kml");
		header("Content-Disposition: attachment; filename=".time().".kmz");
		header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
		header("Cache-Control: cache, must-revalidate");
		header("Pragma: public");
		
		$view = new View("kml");
		$view->kml_name = htmlspecialchars(Kohana::config('settings.site_name'));
		$view->kml_tagline = htmlspecialchars(Kohana::config('settings.site_tagline'));
		$view->items = $incidents;
		$view->categories = $categories;

		// move from view
		$view->kml_filename = $kml_filename;
		$view->kmz_filename = $kmz_filename;
		$view->kmlFileName = $kmlFileName;
		$view->kmzFileName = $kmzFileName;
		

		// set use cache
		$view->use_cache = $use_cache;

		// set cron flag
		$view->cron_flag = $cron_flag;

		$view->render(TRUE);
	}
}
