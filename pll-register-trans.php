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

function anbTopDealsTrans() {
    pll_register_string('Most popular', 'Most popular', 'AnbTopDeals');
    pll_register_string('Installation', 'Installation', 'AnbTopDeals');
    pll_register_string('Activation', 'Activation', 'AnbTopDeals');
    pll_register_string('Info and options', 'Info and options', 'AnbTopDeals');
    pll_register_string('Free installation', 'Free installation', 'AnbTopDeals');
    pll_register_string('Free activation', 'Free activation', 'AnbTopDeals');
    pll_register_string('t.w.v', 't.w.v', 'AnbTopDeals');
    pll_register_string('BEST', 'BEST', 'AnbTopDeals');
    pll_register_string('Promo', 'Promo', 'AnbTopDeals');
    pll_register_string('Offer', 'Offer', 'AnbTopDeals');
    pll_register_string('advantage', 'advantage', 'AnbTopDeals');
    pll_register_string('the first %d months', 'the first %d months', 'AnbTopDeals');
}

add_action('init', 'anbTopDealsTrans');