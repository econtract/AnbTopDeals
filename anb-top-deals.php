<?php
/*
Plugin Name: Aanbieders Top Deals
Depends: Wp Autoload with Namespaces, Aanbieders Api Client, Polylang
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Top deals plugin for Aanbieders.
Version: 1.0.0
Author: Imran Zahoor
Author URI: http://imranzahoor.wordpress.com/
License: A "Slug" license name e.g. GPL2
*/

namespace AnbTopDeals;
include_once(WP_PLUGIN_DIR . "/wpal-autoload/wpal-autoload.php");
// If this file is accessed directory, then abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

include(__DIR__ . '/pll-register-trans.php');
