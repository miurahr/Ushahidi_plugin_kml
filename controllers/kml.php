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
 * @author     Hiroshi Miura <miurahr@osmf.jp>
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
		$default_limit = Kohana::config('kml.default_limit');
		$default_limit = isset($default_limit)?$default_limit:1000;

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
			$category_id = (isset($category_id) AND intval($category_id) > 0)?								intval($category_id):0;
		} else {
			$category_id = 0;
		}

		// cron on?
		if (isset($_GET['cron']) AND !empty($_GET['cron']))
		{
			$cron_flag = true;
			$limit = 0; // execute cron with default limit.
		} else {
			$cron_flag = false;
		}

		// use cdn when normal request
		if ($limit == 0 && $cron_flag == false && $category_id == 0) { 
			url::redirect(Kohana::config("kml.cdn_kml_url"));
		}

		// 2.
		$kml_filename = "latest.kml";  // filename for exported KML file
		$kmz_filename = "latest.kmz";  // filename for exported KMZ file

		if ($category_id == 0)
		{
			$cat_name = "all_";
		}
		 elseif ($category_id > 0)
		{
			$cat_name = "cat".strval($category_id)."_";
		}
		else 
		{
			$cat_name = "";
		}

		if ($limit == -1)
		{
			$kml_filename = $cat_name."full_".$kml_filename;
			$kmz_filename = $cat_name."full_".$kmz_filename;
			$limit = 0; // change to 0 that originaly mean  no limit.
		}
		elseif ($limit != 0) 
		{
			$kml_filename = $cat_name.strval($limit)."_".$kml_filename;
			$kmz_filename = $cat_name.strval($limit)."_".$kmz_filename;
		}
		elseif ($limit == 0 && $category_id == 0 && $cron_flag == true)
		{ 
			// cron request should do with $default names 
			// and $default_limit.
			$limit = $default_limit;
		}
		else
		{
			$limit = $default_limit;
			$kml_filename = $cat_name.$kml_filename;
			$kmz_filename = $cat_name.$kml_filename;
		}

  		// internal path to KML/KMZ file in uploads directory
		$kmlFileName = Kohana::config('upload.directory', TRUE) . $kml_filename;
		$kmzFileName = Kohana::config('upload.directory', TRUE) . $kmz_filename;

		// 3.
		//=== Caching Options ==
		$cache_secs = Kohana::config('kml.cache_secs',TRUE);
		$cache_on = Kohana::config('kml.cache_on',TRUE);
		if ($cache_on && file_exists($kmzFileName)
			&& (time() - filemtime($kmzFileName) < $cache_secs)) {
			$use_cache = true;
			// 4.1
			$incident_items = NULL;
			$categories = NULL;
		}
		else
		{
			$use_cache = false;
			// 4.2
			$incidents = ORM::factory('incident')
					->orderby('incident_date', 'desc');
			if ($category_id == 0)
			{
					$incidents = $incidents->where('incident_active', '1');
			}
			else 
			{
					$incidents = $incidents->join('incident_category', 
						 		'incident_category.incident_id','incident.id',
						   		'INNER')
							->where(array('incident_category.category_id'
						        => strval($category_id),
						      'incident_active' => '1'));
			}
			$incident_items = ($limit != 0) ?$incidents->find_all($limit,0)
											:$incidents->find_all();
		}
		// Get all Categories...
		$categories = ORM::factory('category')
						->where('category_visible', '1')
						->find_all();

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
		$view->kml_filename = $kml_filename;
		$view->kmz_filename = $kmz_filename;
		$view->kmlFileName = $kmlFileName;
		$view->kmzFileName = $kmzFileName;

		$view->items = $incident_items;
		$view->categories = $categories;

		// set use cache
		$view->use_cache = $use_cache;
		// set cron flag
		$view->cron_flag = $cron_flag;

		$view->render(TRUE);
	}
}
