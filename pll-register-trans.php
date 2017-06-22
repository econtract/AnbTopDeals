<?php
/**
 * Created by PhpStorm.
 * User: imran
 * Date: 6/12/17
 * Time: 4:38 PM
 */
if ( !function_exists( 'pll_register_string' ) ) {
    require_once WP_PLUGIN_DIR . '/polylang/include/api.php';
}

pll_register_string('Proximus most popular', 'Proximus most popular', 'AnbTopDeals');
pll_register_string('Installation', 'Installation', 'AnbTopDeals');
pll_register_string('Activation', 'Activation', 'AnbTopDeals');
