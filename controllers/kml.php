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
		// 0. define default limitation for google maps
		// 1. get limit
		// 2. set filename
		// 3. ditect cache
		// 4.1. has cache -> return file
		// 4.2. no cache -> get data from sql, and write in view
        Kohana::config_load('kml');

		// 0.
		// max file size :     3MB
		// max raw KML size : 10MB
		// max network link : 10
		// max items        : 1000 <-- apply
		// max items in view: 80
		$default_limit = 1000;

		// for sinsai.info real data
		// 1000items  6.8MB in raw KML, 0.5MB in KMZ
		// 1500items  9.8MB in raw KML, 1.3MB in KMZ

		// 1.
		if (isset($_GET['l']) AND !empty($_GET['l']))
		{
			$limit = $this->input->xss_clean($_GET['l']);
			$limit = (isset($limit) AND intval($limit) >0)?intval($limit):0;
		} else {
			$limit = 0;
		}

		if (isset($_GET['cat']) AND !empty($_GET['cat'])) {
			$category_id = $this->input->xss_clean($_GET['cat']);
			$category_id = (isset($category_id) AND intval($category_id) >0)?intval($category_id):0;
		} else {
			$category_id = 0;
		}

		// cron on?
		if (isset($_GET['cron']) AND !empty($_GET['cron']))
		{
			$cron_flag = true;
			$limit = 0; // execute cron with no limit.
			//that become $limit = $default_limit;  execute cron with default limit.
		} else {
			$cron_flag = false;
		}

		if ($limit == 0 && $cron_flag == false) { // normal request without limit
			url::redirect(Kohana::config("kml.cdn_kml_url"));
		}

		// 2.
		$kml_filename = "latest.kml";  // filename for exported KML file
		$kmz_filename = "latest.kmz";  // filename for exported KMZ file

		if ($limit == -1)
		{
			$kml_filename = $kml_filename . "_all";
			$kmz_filename = $kmz_filename . "_all";
			$limit = 0; // change to 0 that originaly mean  no limit.
		}
		elseif ($limit != 0) 
		{
			$kml_filename = $kml_filename . "_" . strval($limit);
			$kmz_filename = $kmz_filename . "_" . strval($limit);
		}
		else
		{ 
			// cron request should do with $default_limit but name is without limitval.
			$limit = $default_limit;
		}

		$kmlFileName = Kohana::config('upload.directory', TRUE) . $kml_filename;  // internal path to KML file in uploads directory
		$kmzFileName = Kohana::config('upload.directory', TRUE)  . $kmz_filename;  // internal path to KMZ file in uploads directory

		// 3.

		//=== Caching Options ==
		$cache_secs = 120; 	// seconds during which to serve cached file, after which re-generate on next request
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
			$categories = array();
			if ($category_id == 0) {
				$categories = ORM::factory('category')
					->where('category_visible', '1')
					->find_all();
			} else {
				$categories = ORM::factory('category')
					->where('id', $category_id)
					->where('category_visible', '1')
					->find_all();
			}

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
