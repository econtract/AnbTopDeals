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
                        <img src="' . $prd['logo']['200x140']->transparent->color . '" alt="' . $prd['product_name'] . '">
                    </div>';
        return $logoSec;
    }

    public function getTitleSection(array $prd, $listView = false)
    {
        $titleSec = '<h3 class="col_1">' . $prd['product_name'] . '</h3>';

        if ($listView) {
            $titleSec = '<h3 class="col_1">' . $prd['product_name'] . '</h3>';
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

    public function getBadgeSection($prd)
    {
        // 100% needed to be extracted from $prd array
        $revSec = '101% <span>' . pll__('green') . '</span>';
        return $revSec;
    }

    public function getGreenPeaceRating($product)
    {
	    $greenpeaceScore = ($product->electricity->specifications->greenpeace_score->value) ?: $product->specifications->greenpeace_score->value;
	    $greenpeaceScore = ceil($greenpeaceScore/5);

	    $greenpeaceHtml = '';
	    $counter = 0;
	    for($i = $greenpeaceScore; $i > 0; $i--) {
	    	$j = $i;
	    	$checked = '';
	    	if($i == $greenpeaceScore) {
			    $checked = 'checked = "checked"';
		    }
		    $greenpeaceHtml .= '<input type="radio" id="deal_'.$product->product_id.'_greenPease_'.$j.'" name="deal'.$j.'" value="'.$j.'" '.$checked.' disabled>
                                <label class="full" for="deal_'.$product->product_id.'_greenPease_'.$j.'" title="'.$j.' star"></label>';
		    $counter++;
	    }

	    if($counter < 4) {
		    for($i = $counter; $i < 4; $i++) {
			    $j = $i+1;
			    $greenpeaceHtml = '<input type="radio" id="deal_'.$product->product_id.'_greenPease_'.$j.'" name="deal'.$j.'" value="'.$j.'" disabled>
                                <label class="full" for="deal_'.$product->product_id.'_greenPease_'.$j.'" title="'.$j.' star"></label>'
			                      . $greenpeaceHtml;
		    }
	    }

        $greenPeace = '<div class="greenpeace-container">
                            <div class="peace-logo"></div>
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
            $currPricing = ($product->pricing) ?: $pricing;
            $specs = $currProduct->specifications;
            $greenOriginHtml = $this->greenOriginHtmlFromSpecs($specs);
            $servicesHtml .= '<li>
	                                <span class="icons"><i class="plug-leaf"></i></span>
	                                ' . $greenOriginHtml . '
	                                <span class="desc col_2">' . $specs->tariff_type->label . '</span>
	                                <span class="price yearly">' . formatPrice($currPricing->yearly->promo_price, 2, '&euro; ') . '</span>
	                                <span class="price monthly hide">' . formatPrice($currPricing->monthly->promo_price, 2, '&euro; ') . '</span>
	                            </li>';
        }

        if ($product->producttype == 'dualfuel_pack' || strpos($product->producttype, "gas") !== false) {
            $currProduct = ($product->gas) ?: $product;
            $currPricing = ($product->pricing) ?: $pricing;
            $specs = $currProduct->specifications;
            $greenOriginHtml = $this->greenOriginHtmlFromSpecs($specs);
            $servicesHtml .= '<li>
	                                <span class="icons"><i class="gas-leaf"></i></span>
	                                ' . $greenOriginHtml . '
	                                <span class="desc col_2">' . $specs->tariff_type->label . '</span>
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
    public function greenOriginHtmlFromSpecs($specs)
    {
        $greenOrigin = $specs->green_origin;
        $greenOriginHtml = '<span class="color-green"></span>';
        if ($greenOrigin) {
            $greenOriginHtml = '<span class="color-green">' . intval($greenOrigin->value) . $greenOrigin->unit . '</span>';
        }

        return $greenOriginHtml;
    }

    public function getPromoSection( $product )
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
        $promohtml = '<div class="col_3">
                        <div class="promo">' . pll__('promo') . '</div>';
        if ( count ($promotions) ) {
            $promohtml .= '<ul class="promo-list">';
            foreach ($promotions as $promo ) {

                if(!empty($promo->texts->name)) {
                    $promohtml .= '<li>'.$promo->texts->name.'</li>';
                }
            }
            $promohtml .= '</ul>';
        } else {
            $promohtml .= pll__('No promos found');
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
    public function getPriceHtml($prd, $pricing, $withCalcHtml = false)
    {
        $priceHtml = '';
        $calcHtml = '';

        if ($withCalcHtml) {
            $calcHtml = '<a href="javascript:void(0)" class="custom-icons calc" data-toggle="modal" data-target="#ratesOverview'.$prd['product_id'].'"></a>';
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
	                            ' . $oldPriceYearlyHtml . '
	                            ' . $oldPriceMonthlyHtml . '
	                            ' . $calcHtml . '
	                        </span>
	                        <div class="current-price yearly">
	                            <span class="super">' . $promoPriceYearlyArr['currency'] . '</span>
	                            <span class="current">' . $promoPriceYearlyArr['price'] . '</span>
	                            <span class="super">,' . $promoPriceYearlyArr['cents'] . '</span>
	                            <small>' . pll__('guaranteed 1st year') . '<i class="question-o custom-tooltip" data-toggle="tooltip" title="" data-original-title="' . pll__('guaranteed 1st year') . '">?</i></small>
	                        </div>
	                        <div class="current-price monthly hide">
	                            <span class="super">' . $promoPriceMonthlyArr['currency'] . '</span>
	                            <span class="current">' . $promoPriceMonthlyArr['price'] . '</span>
	                            <span class="super">,' . $promoPriceMonthlyArr['cents'] . '</span>
	                            <small>' . pll__('guaranteed 1st year') . '<i class="question-o custom-tooltip" data-toggle="tooltip" title="" data-original-title="' . pll__('guaranteed 1st year') . '">?</i> </small>
	                        </div>
	                    </div>';

        return $priceHtml;
    }

    public function getLastUpdateDate( $product ){
        return pll__('Last update') .' '. date('d/m/Y H:i', strtotime($product['last_update']));

    }

    function topDealProducts( $atts, $nav = "" ) {
        $atts = shortcode_atts( array(
            'cat'         => '',
            'detaillevel' => [ 'supplier', 'logo', 'services', 'price', 'reviews', 'texts', 'promotions', 'core_features', 'specifications', 'pricing' ],
            'sg'          => 'consumer',
            'product_1'   => [],
            'product_2'   => [],
            'product_3'   => [],
            'lang'        => 'nl',
            'is_active'   => 'no',
            'is_first'    => 'no'

        ), $atts, 'anb_energy_top_deal_products' );

        if ( ! empty( $atts['detaillevel'] ) && is_string( $atts['detaillevel'] ) ) {
            $atts['detaillevel'] = explode( ',', $atts['detaillevel'] );
        }

        if ( empty( $atts['product_1'] ) || empty( $atts['product_2'] ) || empty( $atts['product_3'] ) || empty( $nav ) ) {
            return;
        }

        $nav = sanitize_text_field( $nav );

        pll_register_string( $nav, $nav, 'AnbTopDeals' );

        $params = array_filter( $atts );

        $cats   = [];
        $cats[] = substr( $atts['product_1'], 0, strpos( $atts['product_1'], "|" ) );
        $cats[] = substr( $atts['product_2'], 0, strpos( $atts['product_2'], "|" ) );
        $cats[] = substr( $atts['product_3'], 0, strpos( $atts['product_3'], "|" ) );

        $productId1 = explode('|',$atts['product_1'])[1];
        $productId2 = explode('|',$atts['product_2'])[1];
        $productId3 = explode('|',$atts['product_3'])[1];

        $cats = array_unique( $cats );
        $cacheTime = 86400;

        if(defined('TOP_DEALS_PRODUCT_CACHE_DURATION')) {
            $cacheTime = TOP_DEALS_PRODUCT_CACHE_DURATION;
        }
        /*
        $products = $this->getProducts( array(
            'cat'         => $cats,
            'sg'          => $atts['sg'],
            'lang'        => $atts['lang'],
            'productid'   => array( $atts['product_1'], $atts['product_2'], $atts['product_3'] ),
            'detaillevel' => $atts['detaillevel']
        ), null, false, 0 );//don't cache top deals
        */
        $paramsArray = array(
            'detaillevel'   => $atts['detaillevel'],
            'pref_pids'     => array( $productId1, $productId2, $productId3 ),
            'sg'            => $atts['sg'],
            'lang'          => $atts['lang'],
            'zip'           => '9000',
            'cat'           => $cats[0],
        );
        $anbComp = wpal_create_instance( \AnbSearch\AnbCompare::class );
        $result  = json_decode ( $anbComp->getCompareResults( $paramsArray ) );

        echo '<div id="datadiv" style="display:none"><pre>';
        print_r($result);
        echo '</div></pre>';

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'top_deals_js', plugin_dir_url( __FILE__ ) . 'js/top-deals.js' );

        $htmlWrapper = '';
        if ( $atts['is_first'] == 'yes' ) {
            $htmlWrapper = '<section class="topDeals energyTopDeals">
                        <div class="container">
                            <div class="deals-count">57<span>'.  pll__( 'Top telecom deals' ) . '</span></div>
                            <div class="topDealsWrapper">
                                <h3>' . pll__( 'Most popular' ) . '</h3>	
                                <div class="filterDeals">
                                    <ul class="list-unstyled list-inline">
                                    </ul>
                                </div>
                                <div class="dealsTable topDealsTable">
                                    
                                </div>
                            </div>
                        </div>
                     </section>';
        }

        echo $htmlWrapper;

        //append Navigation to the HTML
        $class        = '';
        $displayStyle = '';

        if ( $atts['is_active'] == 'yes' ) {
            $class = 'class="active"';
        } else {
            $displayStyle = 'style="display:none;"';
        }

        $navHtmlName = sanitize_title_with_dashes( $nav );
        $navContent = '<div class="row family-deals ' . $navHtmlName . '" ' . $displayStyle . '>';

        $idxx = 1;

        foreach ( $result->results as $listProduct ) :
            $boxClass = 'left';
            if ( $idxx == 2 ) {
                $boxClass = 'center';
            } elseif ( $idxx == 3 ) {
                $boxClass = 'right';
            }
            $endScriptTime = getEndTime();
            displayCallTime($startScriptTime, $endScriptTime, "Total page load time for Results page invidual gridView start.");

            $product     = $listProduct->product;
            $pricing     = $listProduct->pricing;
            $productData = $this->prepareProductData( $product );
            $productId   = $product->product_id;

            $endScriptTime = getEndTime();
            displayCallTime($startScriptTime, $endScriptTime, "Total page load time for Results page invidual gridView till prepareProductData.");

            list(, , , , $toCartLinkHtml) = $this->getToCartAnchorHtml($parentSegment, $productData['product_id'], $productData['supplier_id'], $productData['sg'], $productData['producttype'], $forceCheckAvailability);

            $blockLinkClass = 'block-link';
            if($forceCheckAvailability) {
                $blockLinkClass = 'block-link missing-zip';
            }
            $toCartLinkHtml = '<a '.$toCartLinkHtml.' class="link '.$blockLinkClass.'">' . pll__( 'Order Now' ) . '</a>';

            if($productData['commission'] === false) {
                $toCartLinkHtml = '<a href="#not-available" class="link block-link not-available">' . pll__('Not Available') . '</a>';
            }

            $promoPriceYearly = $pricing->yearly->promo_price;
            $promoPriceYearlyArr = formatPriceInParts($promoPriceYearly, 2);
            $aanbidersDiscount = formatPrice( $pricing->yearly->price - $promoPriceYearly, 2, '&euro; ');
            $servicesHtml = '';

            if ($product->producttype == 'dualfuel_pack' || strpos($product->producttype, "electricity") !== false) {
                $currProduct = ($product->electricity) ?: $product;
                $currPricing = ($product->pricing) ?: $pricing;
                $specs = $currProduct->specifications;
                $greenOrigin = $specs->green_origin;
                $greenOriginHtml = '<span class="color-green"></span>';
                if ($greenOrigin) {
                    $greenOriginHtml = '<span class="color-green">' . intval($greenOrigin->value) . $greenOrigin->unit . '</span>';
                }
                $servicesHtml.= '<li>
	                                <span class="icons"><i class="plug-leaf"></i></span>
	                                ' . $greenOriginHtml . '
	                                <span class="desc">' . $specs->tariff_type->label . '</span>
	                                <span class="price">' . formatPrice($currPricing->yearly->promo_price, 2, '&euro; ') . '</span>
	                            </li>';
            }

            if ($product->producttype == 'dualfuel_pack' || strpos($product->producttype, "gas") !== false) {
                $currProduct = ($product->gas) ?: $product;
                $currPricing = ($product->pricing) ?: $pricing;
                $specs = $currProduct->specifications;
                $greenOriginHtml = $this->greenOriginHtmlFromSpecs($specs);
                $servicesHtml.= '<li>
	                                <span class="icons"><i class="gas-leaf"></i></span>
	                                ' . $greenOriginHtml . '
	                                <span class="desc">' . $specs->tariff_type->label . '</span>
	                                <span class="price">' . formatPrice($currPricing->yearly->promo_price, 2, '&euro; ') . '</span>
	                            </li>';
            }

            include(locate_template('template-parts/section/energy-overview-popup.php'));

            $navContent .= '<div class="col-md-4 offer offer-col '.$boxClass.'">
                                <div class="promoLabel">
                                    Best<span>Preview</span>
                                </div>
                                <div class="dealDetails">
                                    <div class="customerRating">
                                        <div class="stamp">'.$productData['score'].'</div>
                                    </div>
                                    <div class="dealLogo">
                                        <img src="' . $productData['logo']['200x140']->transparent->color . '" alt="' . $productData['product_name'] . '">
                                    </div>
                                    <h4>'. $productData['product_name'] .'</h4>
                                    '. $this->getGreenPeaceRating( $product ) .'
                                </div>
                                <div class="deal-health-factors">
                                        <ul>'.$servicesHtml.'</ul>
                                    </div>
                                <div class="saving-board">
                                    <a href="javascript:void(0)" class="calculator" data-toggle="modal" data-target="#ratesOverview'.$productId.'"></a>
                                    <span class="super">€</span>
                                    <span class="saved-amount">136</span>
                                    <small>potential savings</small>
                                </div>
                                <div class="actual-price-board">
                                    <span class="actual-price">'.formatPrice($pricing->yearly->price, 2, '').'</span>
                                    <div class="current-price">
                                        <span class="super">' . $promoPriceYearlyArr['currency'] . '</span>
                                        <span class="current">' . $promoPriceYearlyArr['price'] . '</span>
                                        <span class="super">,' . $promoPriceYearlyArr['cents'] . '</span>
                                        <small>' . pll__('guaranteed 1st year') . '<i class="custom-icons calc"></i></small>
                                    </div>
                                    <div class="discount-price"><span>'. pll__('Aanbieders discount').' -'.$aanbidersDiscount.'</span></div>
                                </div>
                                <div class="dealPrice last">
                                    <div class="lastOrder">
                                        <p></p>
                                    </div>
                                    <div class="buttonWrapper">
                                        <a href="#" class="btn btn-primary all-caps">'. pll__('Details').'</a>
                                        <a href="#" class="link block-link all-caps">'. pll__('Apply for contact').'</a>
                                    </div>
                                </div>
                            </div>';
                        $endScriptTime = getEndTime();
                        displayCallTime($startScriptTime, $endScriptTime, "Total page load time for Results page for individual product listView end.");
                        //unset($productData);//these variables are used in portion right below on calling getProductDetailSection
                        unset($product);
                        //unset($servicesHtml);
            $idxx = $idxx + 1;
            endforeach;
        $navContent .= '</div>';

        $navHtml = '<li ' . $class . '><a href="javascript:void(0);" related="' . $navHtmlName . '">' . pll__( $nav ) . '</a></li>';

        //$script = '<script>appendToSelector(".topDeals .filterDeals ul", {"html": \''.$navHtml.'\'}); appendToSelector(".topDeals .dealsTable", {"html": \''.$navContent.'\'})</script>';
        $script = '<script>
                    jQuery(document).ready(function($){
                        appendToSelector(".topDeals .filterDeals ul",  \'' . $navHtml . '\'); 
                        appendToSelector(".topDeals .dealsTable", \'' . $this->minifyHtml( $navContent ) . '\');
                    });
                   </script>';
        echo $script;
    }
}
