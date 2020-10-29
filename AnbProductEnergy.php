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

    public function __construct()
    {
        $this->anbApi = wpal_create_instance(Aanbieders::class, [$this->apiConf]);
    }

    public function getProductDetailSection($prd, $servicesHtml = '', $includeBadge = false, $badgeTxt = '', $listView = false)
    {

        $detailsSec = '';

        if ($includeBadge && !empty($badgeTxt)) {
            $detailsSec = $this->getBadgeSection($badgeTxt);
        }

        if ($listView) {
            $detailsSec .= $this->getLogoSection($prd, $listView) .
                $this->getTitleSection($prd, $listView) .
                $this->getCustomerRatingSection($prd, $listView);
        } else {
            $detailsSec .= $this->getCustomerRatingSection($prd) .
                $this->getLogoSection($prd) .
                $this->getTitleSection($prd) .
                $this->getServiceIconsSection($servicesHtml);
        }
        return $detailsSec;
    }

    public function getLogoSection(array $prd)
    {
        $logoSec = '<div class="dealLogo">
                        <img src="' . $prd['logo']['200x140']->transparent->color . '" alt="' . htmlentities($prd['product_name']) . '">
                    </div>';
        return $logoSec;
    }

    public function getTitleSection(array $prd, $listView = false)
    {
        $titleSec = '<h3>' . $prd['product_name'] . '</h3>';

        if ($listView) {
            $titleSec = '<h3>' . $prd['product_name'] . '</h3>';
        }

        return $titleSec;
    }

    public function getCustomerRatingSection($prd, $listView = false)
    {
        $custRatSec = '';
        if ((float)$prd['score'] > 0) {
            $custRatSec = '<div class="customer-score"><span>' . $prd['score'] . '  </span>' . pll__('Customer Score') . '</div>';

            if ($listView) {
                $custRatSec = '<div class="customer-score"><span>' . $prd['score'] . '  </span>' . pll__('Customer Score') . '</div>';
            }
        }

        return $custRatSec;
    }

    public function getBadgeSection($badgeText = '', $digits = '')
    {
        // 100% needed to be extracted from $prd array
        $revSec = $digits . ' <span>' . $badgeText . '</span>';
        return $revSec;
    }

    public function getGreenPeaceRating($product = null, $greenpeaceScore = null, $disabledAttr='disabled', $idPrefix = '', $returnWithoutContainer = false)
    {
        $product_id = '';
        if($product) {
            $product_id = $product->product_id;
        }
        $greenpeaceScore = ($greenpeaceScore) ?: (($product->electricity->specifications->greenpeace_score->value) ?: $product->specifications->greenpeace_score->value);
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

    public function getGreenPeaceRatingWithImages($product = null, $greenpeaceScore = null, $returnWithoutContainer = false)
    {
        $product_id = '';
        if($product) {
            $product_id = $product->product_id;
        }
        $greenpeaceScore = ($greenpeaceScore) ?: (($product->electricity->specifications->greenpeace_score->value) ?: $product->specifications->greenpeace_score->value);
        $greenpeaceScore = ceil($greenpeaceScore/5);

        $greenpeaceHtml = '';
        $counter = 0;
        for($i = 0; $i < $greenpeaceScore; $i++) {
            $j = $i;
            $checked = '';
            if($i == $greenpeaceScore) {
                $checked = 'checked = "checked"';
            }

            $greenpeaceHtml .= '<img src="'.get_bloginfo('template_url').'/images/svg-icons/greenpeace-score.svg" />';
            $counter++;
        }

        if($counter < 4) {
            for($i = $counter; $i < 4; $i++) {
                $j = $i+1;
                $greenpeaceHtml = $greenpeaceHtml . '<img src="'.get_bloginfo('template_url').'/images/svg-icons/greenpeace-score-empty.svg" />';
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

    function getServicesHtml($product, $pricing)
    {
        $servicesHtml = '';

        if ($product->producttype == 'dualfuel_pack' || strpos($product->producttype, "electricity") !== false) {
            $currProduct = ($product->electricity) ?: $product;
            if($product->producttype == 'dualfuel_pack') {
                $currPricing = ($product->electricity->pricing) ?: $pricing;
            } else {
                $currPricing = ($product->pricing) ?: $pricing;
            }
            $specs = $currProduct->specifications;
            $greenOriginHtml = $this->greenOriginHtmlFromSpecs($specs);
            $greenRange = $this->greenOriginImageRange($specs);

            $servicesHtml .= '<li class="'.$greenRange.'">
	                                <div class="icons">
	                                    <img class="'.$greenRange.'-icon" src="'.get_bloginfo('template_url').'/images/svg-icons/electricity-'.$greenRange.'.svg" />
	                                </div>
	                                ' . $greenOriginHtml . '
	                                <span class="desc col_4">' . $specs->tariff_type->label . '</span>
	                                <span class="price yearly">' . formatPrice($currPricing->yearly->promo_price, 2, '&euro; ') . '</span>
	                                <span class="price monthly hide">' . formatPrice($currPricing->monthly->promo_price, 2, '&euro; ') . '</span>
	                            </li>';
        }

        if ($product->producttype == 'dualfuel_pack' || strpos($product->producttype, "gas") !== false) {
            $currProduct = ($product->gas) ?: $product;
            if($product->producttype == 'dualfuel_pack') {
                $currPricing = ($product->gas->pricing) ?: $pricing;
            } else {
                $currPricing = ($product->pricing) ?: $pricing;
            }
            $specs = $currProduct->specifications;
            $greenOriginHtml = $this->greenOriginHtmlFromSpecs($specs);
            $greenRange = $this->greenOriginImageRange($specs);

            $servicesHtml .= '<li class="'.$greenRange.'">
	                                <div class="icons">
	                                    <img class="'.$greenRange.'-icon" src="'.get_bloginfo('template_url').'/images/svg-icons/gas-'.$greenRange.'.svg" />
                                    </div>
	                                ' . $greenOriginHtml . '
	                                <span class="desc col_4">' . $specs->tariff_type->label . '</span>
	                                <span class="price yearly">' . formatPrice($currPricing->yearly->promo_price, 2, '&euro; ') . '</span>
	                                <span class="price monthly hide">' . formatPrice($currPricing->monthly->promo_price, 2, '&euro; ') . '</span>
	                            </li>';
        }

        return $servicesHtml;
    }

    /**
     * @param $specs
     *
     * @return string
     */
    public function greenOriginImageRange($specs){
        if($specs->green_origin->value < 50){
            $greenRange = 'green-0';
        } else if($specs->green_origin->value >= 50 && $specs->green_origin->value < 100){
            $greenRange = 'green-50';
        } else {
            $greenRange = 'green-100';
        }
        return $greenRange;
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

    public function getPromoSection( $product , $dataOnly = false )
    {
        $promotions = array();

        if($product->electricity) {
            $promotions = array_merge($promotions, $product->electricity->promotions);
        }

        if($product->gas) {
            $promotions = array_merge($promotions, $product->gas->promotions);
        }

        if(empty($promotions)) {
            $promotions = $product->promotions;
        }

        if($dataOnly){ return $promotions; }

        $promohtml = '<div class="col_5">';
        if ( count ($promotions) ) {
            $promohtml .= '<div class="promo" data-toggle="modal" data-target="#energyPromotionModal'.$product->product_id.'"><img src="'.get_bloginfo('template_url').'/images/svg-icons/Promo.svg" />' . pll__('promo') . '</div>';
            $promohtml .= '<ul class="promo-list" data-toggle="modal" data-target="#energyPromotionModal'.$product->product_id.'">';
            foreach ($promotions as $promo ) {

                if(!empty($promo->texts->name)) {
                    $promohtml .= '<li>'.$promo->texts->name.'</li>';
                }
            }
            $promohtml .= '</ul>';
        } else {
            $promohtml .= '<div class="no-promo"><p></p></div>';
        }
        $promohtml.= '</div>';

        return $promohtml;
    }

    /**
     * @param array $prd
     * @param object $pricing
     * @param bool $withCalcHtml
     *
     * @return string
     */
    public function getPriceHtml($prd, $pricing, $withCalcHtml = false, $isSetCompare = false)
    {
        $priceHtml = '';
        $calcHtml = '';

        if ($withCalcHtml) {
            $calcHtml = '<a href="javascript:void(0)" data-toggle="modal" data-target="#ratesOverview'.$prd['product_id'].'"><img src="'.get_bloginfo('template_url').'/images/svg-icons/calculator@2x.svg" /></a>';
        }

        if ($isSetCompare) {
            $calcHtml = '<a href="javascript:void(0)" data-toggle="modal" data-target="#breakDownPopup'.$prd['product_id'].'"><img src="'.get_bloginfo('template_url').'/images/svg-icons/calculator@2x.svg" /></a>';
        }

        $oldPriceYearlyHtml = '<span class="yearly"></span>';

        if ($pricing->yearly->price != $pricing->yearly->promo_price) {
            $oldPriceYearlyHtml = '<span class="yearly">' . formatPrice($pricing->yearly->price, 2, '') . '</span>';
        }

        $oldPriceMonthlyHtml = '<span class="monthly hide"></span>';

        if ($pricing->monthly->price != $pricing->monthly->promo_price) {
            $oldPriceMonthlyHtml = '<span class="monthly hide">' . formatPrice($pricing->monthly->price, 2, '') . '</span>';
        }

        $promoPriceYearly = $pricing->yearly->promo_price;
        $promoPriceYearlyArr = formatPriceInParts($promoPriceYearly, 2);

        $promoPriceMonthly = $pricing->monthly->promo_price;
        $promoPriceMonthlyArr = formatPriceInParts($promoPriceMonthly, 2);

        $priceHtml = '<div class="actual-price-board">
	                        <span class="actual-price">
	                        	<span class="abi abi-promo"></span>
	                            ' . $oldPriceYearlyHtml . '
	                            ' . $oldPriceMonthlyHtml . '
	                            ' . $calcHtml . '
	                        </span>
	                        <div class="current-price yearly">
	                            ' . $promoPriceYearlyArr['currency'] . '
	                            ' . $promoPriceYearlyArr['price'] . ',' . $promoPriceYearlyArr['cents'] . '
	                            <small class="c-topdeals-description">' . pll__('per year') . '</small>
	                        </div>
	                        <div class="current-price monthly hide">
	                            <span class="super">' . $promoPriceMonthlyArr['currency'] . '</span>
	                            <span class="current">' . $promoPriceMonthlyArr['price'] . '</span>
	                            <span class="super">,' . $promoPriceMonthlyArr['cents'] . '</span>
	                            <small class="col_6">' . pll__('guaranteed 1st month') . '<i class="question-o custom-tooltip b" data-toggle="tooltip" title="" data-original-title="' . pll__('guaranteed 1st month info text') . '">?</i> </small>
	                        </div>
	                    </div>';

        return $priceHtml;
    }

    public function getLastUpdateDate( $product ){
        return pll__('Last update') .' '. date('d/m/Y H:i', strtotime($product['last_update']));

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
