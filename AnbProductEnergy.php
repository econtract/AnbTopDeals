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
	                            <div class="promo-icon">
	                                <img src="'.get_bloginfo('template_url').'/images/svg-icons/Promo.svg" />
	                            </div>
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

    function topDealProducts( $atts, $nav = "" ) {
        $atts = shortcode_atts( array(
            'cat'         => '',
            'detaillevel' => [ 'supplier', 'logo', 'services', 'price', 'reviews', 'texts', 'promotions', 'core_features', 'specifications', 'pricing' ],
            'sg'          => 'consumer',
            'product_1'   => [],
            'product_2'   => [],
            'product_3'   => [],
            'product_4'   => [],
            'product_5'   => [],
            'lang'        => getLanguage(),
            'is_active'   => 'no',
            'is_first'    => 'no'

        ), $atts, 'anb_energy_top_deal_products' );

        if ( ! empty( $atts['detaillevel'] ) && is_string( $atts['detaillevel'] ) ) {
            $atts['detaillevel'] = explode( ',', $atts['detaillevel'] );
        }

        //Temp disabled for restyle testing
//        if ( empty( $atts['product_1'] ) || empty( $atts['product_2'] ) || empty( $atts['product_3'] ) ||  empty( $atts['product_4'] ) ||  empty( $atts['product_5'] ) || empty( $nav ) ) {
//            return;
//        }

        $nav = sanitize_text_field( $nav );

        pll_register_string( $nav, $nav, 'AnbTopDeals' );
        $params = array_filter( $atts );

        $cats = array();
        $cats[] = substr( $atts['product_1'], 0, strpos( $atts['product_1'], "|" ) );
        $cats[] = substr( $atts['product_2'], 0, strpos( $atts['product_2'], "|" ) );
        $cats[] = substr( $atts['product_3'], 0, strpos( $atts['product_3'], "|" ) );
        $cats[] = substr( $atts['product_4'], 0, strpos( $atts['product_4'], "|" ) );
        $cats[] = substr( $atts['product_5'], 0, strpos( $atts['product_5'], "|" ) );

        $productId1 = explode('|',$atts['product_1'])[1];
        $productId2 = explode('|',$atts['product_2'])[1];
        $productId3 = explode('|',$atts['product_3'])[1];
        $productId4 = explode('|',$atts['product_4'])[1];
        $productId5 = explode('|',$atts['product_5'])[1];

        $cats = array_unique( $cats );
        $productType = $cats[0];
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
            'pref_pids'     => array( $productId1, $productId2, $productId3, $productId4, $productId5 ),
            'sg'            => $atts['sg'],
            'lang'          => $atts['lang'],
            'zip'           => '9000',
            'cat'           => $productType,
        );

        if( in_array($productType, array('dualfuel_pack', 'electricity')) ) {
            $paramsArray[ 'du' ] = 1700;
            $paramsArray[ 'nu' ] = 1400;
        }

        if( in_array($productType, array('dualfuel_pack', 'gas')) ) {
            $paramsArray[ 'u' ] = 17100;
        }

        $anbComp = wpal_create_instance( \AnbSearch\AnbCompare::class );
        $result  = json_decode ( $anbComp->getCompareResults( $paramsArray ) );

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'top_deals_js', plugin_dir_url( __FILE__ ) . 'js/top-deals.js' );
        wp_enqueue_script( 'top_deals_slider_js', plugin_dir_url( __FILE__ ) . 'js/top-deals-slider.js' );

        $htmlWrapper = '';
        if ( $atts['is_first'] == 'yes' ) {
            //TODO:
            // Tempory remove class 'topDeals' from section because of restyle will not yet be on other pages
            // Change text in footer to String and create link
            $htmlWrapper = '<section class="energyTopDeals">
                        <div class="container">
                            <div class="row">
                                <div class="topDealsWrapper col-md-8">
                                    <h3>' . pll__( 'Top 5 cheapest rates' ) . '</h3>	
                                    ' . pll__( 'Quickly view the details of our cheapest suppliers and rates.' ) . '
                                    <div class="filterDeals">
                                        <ul class="topDeals-tabs">
                                        </ul>
                                    </div>
                                    <div class="dealsTable topDealsTable">
                                        
                                    </div>
                                    <div class="topDealsFooter">Lorem ipsum dolor sit amet <a href="#">Hoe komen we aan deze top 5?</a></div>
                                </div>
                                <div class="topDealsSidebar col-md-4">
                                    '.$this->getSidebar().'
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

        $navHtmlName = sanitize_title_with_dashes( remove_accents ($nav ) );
        //$navContent = '<div class="row family-deals ' . $navHtmlName . '" ' . $displayStyle . '>';
        //$navContent  = '<div class="slider-' . $navHtmlName . ' custom-deals owl-theme owl-carousel row family-deals ' . $navHtmlName . '" ' . $displayStyle . '>';
        $navContent  = '<div class="custom-deals row family-deals ' . $navHtmlName . '" ' . $displayStyle . '>';

        $idxx = 1;

        foreach ( $result->results as $listProduct ) :
            $boxClass = 'left';
            if ( $idxx == 2 ) {
                $boxClass = 'center';
            } elseif ( $idxx == 3 ) {
                $boxClass = 'right';
            }
            $topOrder = $idxx; //top5 order
            $startScriptTime = getStartTime();
            $endScriptTime = getEndTime();
            displayCallTime($startScriptTime, $endScriptTime, "Total page load time for Results page invidual gridView start.");

            $product     = $listProduct->product;
            $pricing     = $listProduct->pricing;
            $productData = $this->prepareProductData( $product );
            $productId   = $product->product_id;
            $supplierId  = $product->supplier_id;
            $segment     = $product->segment;
            $productType = $product->producttype;
            $parentSegment = getSectorOnCats( [$productType] );

            $endScriptTime = getEndTime();
            displayCallTime($startScriptTime, $endScriptTime, "Total page load time for Results page invidual gridView till prepareProductData.");

            $forceCheckAvailability = false;
            $missingZipClass = '';
            if(empty($_GET['zip'])) {
                $forceCheckAvailability = true;
                $missingZipClass = 'missing-zip';
            }

            list(, , , , $toCartLinkHtml) = $this->getToCartAnchorHtml($parentSegment, $productData['product_id'], $productData['supplier_id'], $productData['sg'], $productData['producttype'], $forceCheckAvailability);
            $toCartLinkHtml = '<a class="btn btn-primary all-caps btn-missing-zip-enery" data-pid="' . $productId . '" data-sid="' . $supplierId . '" data-sg="' . $segment . '" data-prt="' . $productType . '">'. pll__('connect now') .'</a>
                                <a href="'.getEnergyProductPageUri($productData).'" class="link block-link all-caps">'.pll__('Detail').'</a>';
            //TODO cleanup/rewrite
            $toCartLinkHtml2 = '<a href="'.getEnergyProductPageUri($productData).'" data-pid="' . $productId . '" data-sid="' . $supplierId . '" data-sg="' . $segment . '" data-prt="' . $productType . '"><img src="'.get_bloginfo('template_url').'/images/svg-icons/CTA-round-arrow.svg" /></a>';

            if($productData['commission'] === false) {
                $toCartLinkHtml = '<a href="#not-available" class="btn btn-default all-caps not-available">' . pll__('Not Available') . '</a>';
            }

            $promoPopUpLogo = $this->getLogoSection($productData);

            $promoPopUpDetailsArr = $this->getPromoSection($product, true);

            $servicesHtml = '';

            if ($product->producttype == 'dualfuel_pack' || strpos($product->producttype, "electricity") !== false) {
                $currProduct = ($product->electricity) ?: $product;
                $currPricing = ($product->pricing) ?: $pricing;
                $specs = $currProduct->specifications;
                $greenOrigin = $specs->green_origin;
                $greenOriginHtml = '<div class="color-green"></div>';
                if ($greenOrigin) {
                    $greenOriginHtml = '<div class="color-green">' . intval($greenOrigin->value) . $greenOrigin->unit . '</div>';
                }
                $greenRange = $this->greenOriginImageRange($specs);
                $servicesHtml.= '<li class="'.$greenRange.'">
	                                <div class="icons">
	                                    <img src="'.get_bloginfo('template_url').'/images/svg-icons/electricity-'.$greenRange.'.svg" class="'.$greenRange.'-icon" />
	                                </div>
	                                ' . $greenOriginHtml . '
	                                <div class="c-topdeals-description">' . $specs->tariff_type->label . '</div>
	                                <span class="c-price-tag">' . formatPrice($currPricing->yearly->promo_price, 2, '&euro; ') . '</span>
	                            </li>';
            }

            if ($product->producttype == 'dualfuel_pack' || strpos($product->producttype, "gas") !== false) {
                $currProduct = ($product->gas) ?: $product;
                $currPricing = ($product->pricing) ?: $pricing;
                $specs = $currProduct->specifications;
                $greenOriginHtml = $this->greenOriginHtmlFromSpecs($specs);
                $greenRange = $this->greenOriginImageRange($specs);
                $servicesHtml.= '<li class="'.$greenRange.'">
	                                <span class="icons">
	                                    <img src="'.get_bloginfo('template_url').'/images/svg-icons/gas-'.$greenRange.'.svg" class="'.$greenRange.'-icon" />
	                                </span>
	                                ' . $greenOriginHtml . '
	                                <div class="c-topdeals-description">' . $specs->tariff_type->label . '</div>
	                                <span class="c-price-tag">' . formatPrice($currPricing->yearly->promo_price, 2, '&euro; ') . '</span>
	                            </li>';
            }

            $yearAdv = $pricing->yearly->advantage;
            if($yearAdv !== 0):
                $yearAdvArr = formatPriceInParts($yearAdv, 2);
                $monthlyAdv = $pricing->monthly->advantage;
                $monthAdvArr = formatPriceInParts($monthlyAdv, 2);
                $yearAdvHTML = '<div class="price-label ">
                                    <label>'.pll__('Discount').'</label>
                                    <div class="price yearly">'.$yearAdvArr['currency'].' '.$yearAdvArr['price'].'
                                    </div>
                                    <div class="price monthly hide">'.
                                        $monthAdvArr['currency']. ' ' . $monthAdvArr['price'].'
                                    </div>
                                </div>';
            endif;

            include(locate_template('template-parts/section/energy-overview-popup.php'));
            include(locate_template('template-parts/section/energy-promotions-popup.php'));

            $highLightFirst = '';
            if($topOrder == 1){
                $highLightFirst = 'is-highlighted-result c-results__item-counter';
            }else {
                $highLightFirst = 'c-results__item-counter';
            }
            //TODO: Strings
            $navContent .= '<div class="result-box-container col-xs-12 offer offer-col">
                                <div class="result-box">
                                    <div class="top-label">'.$this->getBadgeSection( '' ).'</div>
                                    <div class="row ' . $navHtmlName . '">
                                        <div class="flex-grid">
                                            <span class="'.$highLightFirst.'">'.$topOrder.'</span>
                                            <div class="cols col-xs-2">
                                                '.$this->getLogoSection($productData).'
                                            </div>
                                            <div class="cols col-xs-4">
                                                <h3>'. $productData['supplier_name'] .' - '. $productData['product_name'] .'</h3>
                                                <div class="welcomeDiscount">
                                                    <img src="'.get_bloginfo('template_url').'/images/svg-icons/Promo.svg" />
                                                    '.sprintf(pll__('%s welkomskorting %s'),$productData['supplier_name'], formatPrice($yearAdv)).'
                                                </div>
                                            </div>
                                            <div class="cols col-xs-2">
                                                '.$yearAdvHTML.'
                                            </div>
                                            <div class="cols col-xs-3">
                                                '.$this->getPriceHtml( $productData, $pricing, true, false ).'
                                            </div>
                                            <div class="cols col-xs-1">
                                                <div class="col_11 bottomBtnDv border-top">'.$toCartLinkHtml2.'</div>
                                            </div>
                                        </div>
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

        //Temp for restyle TODO: Insert right icons, active, hover states cleanup
        if ($navHtmlName == 'elektriciteit-en-gas') {
            $tabIcon = 'dualfuel_pack.svg';
        } elseif ($navHtmlName == 'electriciteit') {
            $tabIcon = 'electricity.svg';
        } elseif ($navHtmlName == 'vast-tarief') {
            $tabIcon = 'fixed-rate-active.svg';
        } elseif ($navHtmlName == 'duurzame-energie') {
            $tabIcon = 'settings-sustainable.svg';
        }
        $navHtml2 = '<li ' . $class . '><img src="'.get_bloginfo('template_url').'/images/svg-icons/'.$tabIcon.'" /><a href="javascript:void(0);" related="' . $navHtmlName . '">' . pll__( $nav ) . '</a></li>';

        //$script = '<script>appendToSelector(".topDeals .filterDeals ul", {"html": \''.$navHtml.'\'}); appendToSelector(".topDeals .dealsTable", {"html": \''.$navContent.'\'})</script>';
        $script = '<script>
                    jQuery(document).ready(function($){
                        appendToSelector(".topDeals .filterDeals ul",  \'' . $navHtml . '\'); 
                        appendToSelector(".topDeals .dealsTable", \'' . $this->minifyHtml( $navContent ) . '\');
                        //temp for restyle
                        appendToSelector(".energyTopDeals .filterDeals ul",  \'' . $navHtml2 . '\'); 
                        appendToSelector(".energyTopDeals .dealsTable", \'' . $this->minifyHtml( $navContent ) . '\');
                        //appendToSelector(".energyTopDeals.TopDealsSidebar", \'' . $this->minifyHtml( $sidebarContent ) . '\')
                        
                    });
                   </script>';
        echo $script;
    }

    public function getSidebar(){
        //TODO: change to proper way of linking to send your invoice page
        $invoice = pll__('Send us your invoice');
        $invoiceLink = pll__('stuur je factuur');
        $invoiceLink2 = sanitize_title_with_dashes( remove_accents ($invoiceLink ) );

        $reviewCount = 10;
        $reviewScore = 1;

        $sidebarContent = '<div class="row topDealsVideo">
                                <div class="col">
                                <h4>' . pll__('We are Aanbieders') . '</h4>
                                <div class="videoRatio">
                                    <iframe src="https://www.youtube.com/embed/SbgPWWd88tg" frameborder="0"></iframe>
                                </div>
                                <ul>
                                    <li>' . pll__('100% independent') . '</li>
                                    <li>' . pll__('100% free') . '</li>
                                    <li>' . pll__('Easy to switch') . '</strong></li>
                                    <li>' . pll__('Everything is taking care of') . '</li>
                                    <li>' . pll__('Guarantee up to date') . '</li>
                                </ul>
                                </div>
                           </div>
                           <div class="row topDealsInvoice">
                                <div class="col">
                                    <div class="row">
                                         <div class="col-xs-3">
                                            <img src="' . get_bloginfo('template_url') . '/images/svg-icons/send-invoice-green.svg">
                                        </div>
                                        <div class="col-xs-9">
                                            <h4>' . pll__('Show us your invoice') . '</h4>
                                            <a href="/' . $invoiceLink2 . '/" class="link">' . $invoice . '</a>
                                        </div>
                                    </div>
                                </div>
                           </div>
                           <div class="row topDealsCustomer">
                                <div class="col">
                                    ' . $this->getsidebarGoogleContent() .'
                                </div>
                           </div>
                                           ';
        return $sidebarContent;
    }

    public function getSidebarGoogleContent()
    {
        $language = strtolower(getLanguage());
        $reviewScore = $language === 'fr' ? get_option('google_reviews_mesfournisseurs_score') : get_option('google_reviews_aanbieders_score');
        $reviewCount = $language === 'fr' ? get_option('google_reviews_mesfournisseurs_count') : get_option('google_reviews_aanbieders_count');

        $reviewFormatted = str_replace('.', ',', $reviewScore) ;
        $starSpan = (int)(90 * ($reviewScore / 5)); //90 is width as set in _sect_top_deal.scss
        //TODO: proper way to set string
        $sidebarGoogleContent = '
                        <h4>' . pll__('Customer score') . '</h4>
                        <li class="google-reviews">
                            <span class="score-container">
                                <span class="score">'. $reviewFormatted. '</span>
                                <span class="stars">
                                    <span class="stars-inner" style="width: '. $starSpan .'px"></span>
                                </span>
                            </span>
                            <p>' . pll__('A score of '. $reviewFormatted. ' out of  5 based on ' . $reviewCount . ' reviews on Google') . '</p>
                        </li>
                        ';
        return $sidebarGoogleContent;
    }

    public function getPotentialSavings($savings, $currentSupplierName = false)
    {
        $html = '';
        if(is_object($savings)){
	        $priceYearly = formatPriceInParts($savings->yearly->promo_price,2);
	        $priceMontly = formatPriceInParts($savings->monthly->promo_price,2);
        } else {
	        $priceYearly = $priceMontly = formatPriceInParts(0,2);
        }
        if($savings->yearly->promo_price > 0) {
            $html = '<div class="price-label ">
                        <label>' . pll__('Potential saving') . '</label>
                        <div class="price yearly">' . $priceYearly['currency'] . ' ' . $priceYearly['price'] . '<small>,' . $priceYearly['cents'] . '</small></div>
                        <div class="price monthly hide">' . $priceMontly['currency'] . ' ' . $priceMontly['price'] . '<small>,' . $priceMontly['cents'] . '</small></div>';
            if($currentSupplierName){
                $html .= '<span class="product-discount-thanks-to">'.pll__('t.o.v.') .' '. shortenProductName($currentSupplierName)." ".pll__('Dankzij aanbieders.be').'</span>';
            }
            $html .='</div>';
        }
        return $html;
    }
}
