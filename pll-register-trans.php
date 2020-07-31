<?php
/**
 * Created by PhpStorm.
 * User: imran
 * Date: 6/12/17
 * Time: 4:38 PM
 */
if ( !function_exists( 'pll_register_string' ) ) {
	if(!file_exists(WP_PLUGIN_DIR . '/polylang-pro/include/api.php')) {
		require_once WP_PLUGIN_DIR . '/polylang/include/api.php';
	} else {
		require_once WP_PLUGIN_DIR . '/polylang-pro/include/api.php';
	}
}

function anbTopDealsTrans() {
    pll_register_string('Most popular', 'Most popular', 'AnbTopDeals');
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

    pll_register_string('per year','per year', 'AnbTopDeals');
    pll_register_string('Top 5 cheapest rates', 'Top 5 cheapest rates', 'AnbTopDeals');
    pll_register_string('Quickly view the details of our cheapest suppliers and rates.', 'Quickly view the details of our cheapest suppliers and rates.','AnbTopDeals' );
    pll_register_string('Discount', 'Discount', 'AnbTopDeals');
    pll_register_string('Send us your invoice', 'Send us your invoice', 'AnbTopDeals');
    pll_register_string('We are Aanbieders', 'We are Aanbieders', 'AnbTopDeals');
    pll_register_string('100% independent', '100% independent', 'AnbTopDeals');
    pll_register_string('100% free', '100% free','AnbTopDeals');
    pll_register_string('Easy to switch', 'Easy to switch', 'AnbTopDeals');
    pll_register_string('Everything is taking care of', 'Everything is taking care of', 'AnbTopDeals');
    pll_register_string('Guarantee up to date','Guarantee up to date', 'AnbTopDeals');
    pll_register_string('Show us your invoice','Show us your invoice', 'AnbTopDeals');
    pll_register_string('Customer score','Customer score', 'AnbTopDeals');
    pll_register_string('Show us your invoice','Show us your invoice', 'AnbTopDeals');
    pll_register_string('Hoe komen we aan deze top 5?', 'Hoe komen we aan deze top 5?', 'AnbTopDeals');

    //Energy Short Tab Names
    pll_register_string('dualfuel_pack_shortTabName', 'dualfuel_pack_shortTabName', 'AnbTopDeals');
    pll_register_string('electricity_shortTabName', 'electricity_shortTabName', 'AnbTopDeals');
    pll_register_string('gas_shortTabName_shortTabName', 'gas_shortTabName', 'AnbTopDeals');
    pll_register_string('fixed_rate_shortTabName', 'fixed_rate_shortTabName', 'AnbTopDeals');
    pll_register_string('sustainable_energy_shortTabName', 'sustainable_energy_shortTabName', 'AnbTopDeals');

    //Energy Footer
    pll_register_string('dualfuel_pack_footer', 'dualfuel_pack_footer', 'AnbTopDeals');
    pll_register_string('electricity_footer', 'electricity_footer', 'AnbTopDeals');
    pll_register_string('gas_footer', 'gas_footer', 'AnbTopDeals');
    pll_register_string('fixed_rate_footer', 'fixed_rate_footer', 'AnbTopDeals');
    pll_register_string('sustainable_energy_footer', 'sustainable_energy_footer', 'AnbTopDeals');

    //All-in-1 Short Tab Names
    pll_register_string('popular_deals_shortTabName', 'popular_deals_shortTabName_shortTabName', 'AnbTopDeals');
    pll_register_string('family_deals_shortTabName', 'family_shortTabName_shortTabName', 'AnbTopDeals');
    pll_register_string('mobile_pack_shortTabName', 'mobile_pack_shortTabName', 'AnbTopDeals');

    //All-in-1 Footer
    pll_register_string('popular_deals_footer', 'popular_deals_footer_footer', 'AnbTopDeals');
    pll_register_string('family_deals_footer', 'family_footer_footer', 'AnbTopDeals');
    pll_register_string('mobile_pack_footer', 'mobile_pack_footer', 'AnbTopDeals');

    //Mobile Short Tab Names
    pll_register_string('calling_and_data_shortTabName', 'calling_and_data_shortTabName', 'AnbTopDeals');
    pll_register_string('calling_only_shortTabName', 'calling_only_shortTabName', 'AnbTopDeals');
    pll_register_string('data_only_shortTabName', 'data_only_shortTabName', 'AnbTopDeals');

    //Mobile Footer
    pll_register_string('calling_and_data_footer', 'calling_and_data_footer', 'AnbTopDeals');
    pll_register_string('calling_only_footer', 'calling_only_footer', 'AnbTopDeals');
    pll_register_string('data_only_footer', 'data_only_footer', 'AnbTopDeals');
    
}

add_action('init', 'anbTopDealsTrans');