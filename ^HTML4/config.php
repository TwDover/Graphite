<?php
/** **************************************************************************
 * Project     : Graphite
 *                Simple MVC web-application framework
 * Created By  : LoneFry
 *                dev@lonefry.com
 * License     : CC BY-NC-SA
 *                Creative Commons Attribution-NonCommercial-ShareAlike
 *                http://creativecommons.org/licenses/by-nc-sa/3.0/
 *
 * File        : /^HTML4/config.php
 *                website HTML4 skin configuration file
 ****************************************************************************/

// do not set configurations for ^HTML4 if ^HTML5 is present.
if (file_exists(dirname(__DIR__).'/^HTML5')) {
	return;
}

G::$G['VIEW']['_link'][] = array('rel' => 'stylesheet','type' => 'text/css','href' => '/^HTML4/css/default.css');
