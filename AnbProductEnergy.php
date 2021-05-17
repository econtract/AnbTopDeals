<?php
/**
 * Created by PhpStorm.
 * User: imran
 * Date: 5/12/17
 * Time: 6:02 PM
 */

namespace AnbTopDeals;

use AnbApiClient\Aanbieders;
use AnbSearch\AnbCompare;

class AnbProductEnergy extends AnbProduct
{

    public function getGreenPeaceRating($product = null, $greenpeaceScore = null, $disabledAttr='disabled', $idPrefix = '', $returnWithoutContainer = false)
    {
        $product_id = '';
        if($product) {
            $product_id = $product->product_id;
            $greenpeaceScore = isset($product->electricity) ? $product->electricity->specifications->greenpeace_score->value : $product->specifications->greenpeace_score->value;
        } else {
            $greenpeaceScore = $greenpeaceScore ?: 0;
        }

        $greenpeaceScore = ceil($greenpeaceScore/5);

        $greenpeaceHtml = '';
        $counter = 0;
        for($i = $greenpeaceScore; $i > 0; $i--) {
            $j = $i;
            $checked = '';
            if($i == $greenpeaceScore) {
                $checked = 'checked = "checked"';
            }

            $greenpeaceHtml .= '<input type="radio" id="'.$idPrefix.'deal_'.$product_id.'_greenPease_'.$j.'" name="greenpeace'.$product_id.'" value="'.$j.'" '.$checked.' '.$disabledAttr.' greenpeace="'.$greenpeaceScore.'">
                                <label class="full" for="'.$idPrefix.'deal_'.$product_id.'_greenPease_'.$j.'" title="'.$j.' star"></label>';
            $counter++;
        }

        if($counter < 4) {
            for($i = $counter; $i < 4; $i++) {
                $j = $i+1;
                $greenpeaceHtml = '<input type="radio" id="'.$idPrefix.'deal_'.$product_id.'_greenPease_'.$j.'" name="greenpeace" value="'.$j.'" '.$disabledAttr.' greenpeace="'.$greenpeaceScore.'">
                                <label class="full" for="'.$idPrefix.'deal_'.$product_id.'_greenPease_'.$j.'" title="'.$j.' star"></label>'
                    . $greenpeaceHtml;
            }
        }

        if($returnWithoutContainer) {
            return $greenpeaceHtml;
        }

        $greenPeace = '<div class="greenpeace-container">
                            <div class="peace-logo"><img src="'.get_bloginfo('template_url').'/images/svg-icons/greenpeace-logo.svg" /></div>
                            <fieldset>
                                '.$greenpeaceHtml.'
                                <div class="clearfix"></div>
                            </fieldset>
                        </div>';
        return $greenPeace;
    }

    /**
     * @param $specs
     *
     * @return string
     */
    public function greenOriginHtmlFromSpecs($specs)
    {
        $greenOrigin = $specs->green_origin;
        $greenOriginHtml = '<span class="color-green"></span>';
        if ($greenOrigin) {
            $greenOriginHtml = '<span class="color-green">' . intval($greenOrigin->value) . $greenOrigin->unit . '</span>';
        }

        return $greenOriginHtml;
    }

    function topDealProductsNew( $atts, $tabName = "" )
    {
        $atts = shortcode_atts(array(
            'cat'           => '',
            'tab_cat'       => 'dualfuel_pack',
            'tabname_short' => '',
            'footer_cat'    => '',
            'detaillevel'   => ['supplier', 'logo', 'services', 'price', 'reviews', 'texts', 'promotions', 'core_features', 'specifications', 'pricing', 'all_features'],
            'sg'            => 'consumer',
            'product_1'     => [],
            'product_2'     => [],
            'product_3'     => [],
            'product_4'     => [],
            'product_5'     => [],
            'lang'          => getLanguage(),
            'is_active'     => 'no',
            'is_first'      => 'no',
        ), $atts, 'anb_energy_top_deal_products_new');

        if (!empty($atts['detaillevel']) && is_string($atts['detaillevel'])) {
            $atts['detaillevel'] = explode(',', $atts['detaillevel']);
        }

        if (empty($tabName)) {
            $tabName = pll__($atts['tab_cat']);
        }

        $tabName = sanitize_text_field($tabName);

        if (empty($atts['tabname_short'])) {
            $atts['tabname_short'] = $tabName;
        }

        if (is_string($atts['product_1'])) {
            $productType = substr($atts['product_1'], 0, strpos($atts['product_1'], "|"));
        } else {
            $productType = isset($atts['product_1'][0]) ? $atts['product_1'][0] : null;
        }

        if (!$productType) {
            return;
        }

        $paramsArray = [
            'detaillevel' => $atts['detaillevel'],
            'pref_pids'   => $this->getProductIdsFromAttributes($atts),
            'sg'          => $atts['sg'],
            'lang'        => $atts['lang'],
            'zip'         => '9000',
            'cat'         => $productType,
        ];

        if (in_array($productType, array('dualfuel_pack', 'electricity'))) {
            $paramsArray['du'] = 1700;
            $paramsArray['nu'] = 1400;
        }

        if (in_array($productType, array('dualfuel_pack', 'gas'))) {
            $paramsArray['u'] = 17100;
        }

        /** @var AnbCompare $anbCompare */
        $anbCompare = wpal_create_instance(\AnbSearch\AnbCompare::class);
        $result     = json_decode($anbCompare->getCompareResults($paramsArray));

        $tabID       = sanitize_title_with_dashes(remove_accents($tabName)) . '-' . rand(0, 999);
        $tabIsActive = isset($atts['is_active']) && $atts['is_active'] === 'yes';
        $deals       = $result->results;
        $footerText  = $atts['footer_cat'];

        ob_start();

        include locate_template('template-parts/section/top-deals/deals.php');

        $tabContent = '<div id="' . $tabID . '" class="tab-pane ' . ($tabIsActive ? 'active' : '') . '">' . ob_get_clean() . '</div>';

        if ($atts['tab_cat'] == 'dualfuel_pack') {
            $tabIcon = 'abf abf-dualfuel-pack';
        } elseif ($atts['tab_cat'] == 'electricity') {
            $tabIcon = 'abf abf-electricity';
        } elseif ($atts['tab_cat'] == 'gas') {
            $tabIcon = 'abf abf-gas';
        } elseif ($atts['tab_cat'] == 'fixed_rate') {
            $tabIcon = 'abf abf-fixed-rate';
        } elseif ($atts['tab_cat'] == 'sustainable_energy') {
            $tabIcon = 'abf abf-sustainable-energy';
        } else {
            $tabIcon = 'abf abf-dualfuel-pack';
        }
        $tabClass     = $tabIsActive ? 'active' : '';
        $tabItem      = '<li class="' . $tabClass . ' is-tab">';

        $tabItem .= '<a href="#' . $tabID . '" data-toggle="tab">';
        if (!empty($tabIcon)) {
            $tabItem .= '<i class="' . $tabIcon . '" /></i> ';
        }
        $tabItem .= '<span class="hidden-xs">' . $tabName . '</span><span class="visible-xs">' . $atts['tabname_short'] . '</span></a></li>';

        $script = '<script>
                    jQuery(document).ready(function($){
                        $(\'.top-deals .tabs ul\').append(\'' . $tabItem . '\');
                        $(\'.top-deals .tab-content\').append(\'' . $this->minifyHtml($tabContent) . '\');
                        $(\'.top-deals\').removeClass(\'loading\');
                        $(\'.top-deals .loading\').removeClass(\'loading\');
                    });
                   </script>';
        echo $script;
    }
}
