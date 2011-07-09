<?php defined('SYSPATH') or die('No direct script access.');
/**
 * KML helper
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   Ushahidi Team <team@ushahidi.com> 
 * @author     Hiroshi Miura <miurahr@osmf.jp>
 * @package    Ushahidi - http://source.ushahididev.com
 * @module	   KML Controller	
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
* 
*/
class kml_Core {

	function get_kmlsite()
	{
		if (Kohana::config("kml.kmlsite") != null AND 
			strlen(Kohana::config("kml.kmlsite")) > 7 )
		{
			$urlbase = Kohana::config("kml.kmlsite");
		}
		else
		{
			$urlbase = url::base();
		}
		return $urlbase;
	}

	//=== function to get domain name from URL string
	function get_domain($url) {
		// parse host out of URL
		$host = parse_url($url, PHP_URL_HOST);
		// remove first subdomain if it matches one in the list (with ".")
		$sub_domains_remove = array("www.", "www2.", "blog.", "blogs.");
		$domain = str_replace($sub_domains_remove, "", $host);
		// remove additional subdomains if more than 2 "."s (dots)
		while (substr_count($domain, ".") > 2) {
			$remove_upto = strpos(".", $domain) + 1;
			$domain = substr($domain, $remove_upto);
		}
		return $domain;
	}


}
