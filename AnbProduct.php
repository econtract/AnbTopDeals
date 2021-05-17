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

if (!function_exists('getLanguage')) {
    function getLanguage()
    {
        //get locale
        $locale = function_exists('pll_current_language') ? pll_current_language() : Locale::getPrimaryLanguage(get_locale());

        return $locale;
    }
}

class AnbProduct
{
    /** @var string  */
    public $crmApiEndpoint = "http://api.econtract.be/";//Better to take it from Admin settings

    /** @var $anbApi \AnbApiClient\Aanbieders */
    public $anbApi;

    /** @var array */
    public $apiConf = [
        'host'    => ANB_API_HOST,
        'staging' => ANB_API_STAGING,
        'key'     => ANB_API_KEY,
        'secret'  => ANB_API_SECRET,
    ];

    private $producttypesToSkipPromos = ['mobile_internet', 'mobile'];

    public function __construct()
    {
        $this->anbApi = wpal_create_instance(Aanbieders::class, [$this->apiConf]);
    }

    function topDealProductsNew($atts, $tabName = "")
    {
        $atts = shortcode_atts(array(
            'cat'           => '',
            'tab_cat'       => 'popular_deals',
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
        ), $atts, 'anb_top_deal_products_new');

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
            'cat'         => $productType,
        ];

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

        if ($atts['tab_cat'] == 'popular_deals') {
            $tabIcon = 'abf abf-wifi';
        } elseif ($atts['tab_cat'] == 'family_deals') {
            $tabIcon = 'abf abf-family';
        } elseif ($atts['tab_cat'] == 'mobile_pack') {
            $tabIcon = 'abf abf-mobile-phone';
        } elseif ($atts['tab_cat'] == 'calling_and_data') {
            $tabIcon = 'abf abf-mobile-calling-data';
        } elseif ($atts['tab_cat'] == 'calling_only') {
            $tabIcon = 'abf abf-mobile-calling';
        } elseif ($atts['tab_cat'] == 'data_only') {
            $tabIcon = 'abf abf-mobile-data';
        } else {
            $tabIcon = 'abf abf-dualfuel-pack';
        }
        $tabClass = $tabIsActive ? 'active' : '';
        $tabItem  = '<li class="' . $tabClass . ' is-tab">';

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

    /**
     * @param array $atts
     * @param int   $amount
     * @return array
     */
    function getProductIdsFromAttributes($atts, $amount = 5) {

        $productIds = [];

        // When called as a shortcode, products are formatted like `[product_type]|[product_id]`
        foreach (range(1, $amount) as $index) {
            $key = 'product_' . $index;
            if (!isset($atts[$key])) {
                continue;
            }
            $product = $atts[$key];
            if (is_string($atts[$key])) {
                $product = explode('|', $product);
            }
            if (!isset($product[1])) {
                continue;
            } else {
                $productIds[] = $product[1];
            }
        }

        return $productIds;
    }

    /**
     * @param object     $product
     * @param array|null $order
     * @return string[][]
     */
    function getCoreFeatures($product, $order = null)
    {
        // Core features are not included, so return empty array
        if (!property_exists($product, 'core_features') || empty($product->core_features)) {
            return [];
        }

        $productType = $product->producttype;
        if ($productType !== 'packs') {
            $features      = $product->core_features->{$productType};
            $featureLabels = array_map(function ($feature) {
                return $feature->label;
            }, $features);

            return [$productType => $featureLabels];
        }

        // Default order
        $order = isset($order) ? $order : ['internet', 'idtv', 'telephony', 'mobile', 'mobile_internet'];

        $coreFeatures = [];
        foreach ($order as $packType) {
            if (property_exists($product, $packType)) {
                $features = $product->{$packType}->core_features->{$packType};
                foreach ($features as $feature) {
                    $coreFeatures[$packType][] = $feature->label;
                }
            }
        }

        return $coreFeatures;
    }

    function minifyHtml($buffer)
    {

        $search = array(
            '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
            '/[^\S ]+\</s',     // strip whitespaces before tags, except space
            '/(\s)+/s',         // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/' // Remove HTML comments
        );

        $replace = array(
            '>',
            '<',
            '\\1',
            '',
        );

        $buffer = preg_replace($search, $replace, $buffer);
        $buffer = str_replace("'", "\'", $buffer);

        return $buffer;
    }

    /**
     * @param object $product
     *
     * @return array
     */
    public function prepareProductData($product)
    {
        $data = [];
        //for now hardcoded decimal separator to coma
        //Pack type: 'int_tel', 'tv_tel', 'int_tel_tv', 'gsm_int_tel_tv', 'int_tv', 'gsm_int_tv'
        $data['producttype'] = $product->producttype;

        if ($product->producttype == "packs") {
            $data['packtype']  = $product->packtype;
            $data['packtypes'] = array_keys((array)$product->packtypes);
        }

        $data['product_name']  = $product->product_name;
        $data['sg']            = $product->segment;
        $data['last_update']   = $product->last_update;
        $data['product_slug']  = $product->product_slug;
        $data['supplier_id']   = $product->supplier_id;
        $data['supplier_slug'] = $product->supplier_slug;
        $data['supplier_name'] = $product->supplier_name;
        $data['product_id']    = $product->product_id;
        $data['tagline']       = isset($product->texts->tagline) ? $product->texts->tagline : null;
        $data['price']         = isset($product->price) ? (array)$product->price : null;
        $data['monthly_fee']   = isset($product->monthly_fee) ? (array)$product->monthly_fee : [];
        $data['advantage']     = isset($product->price->advantage) ? $product->price->advantage : null;
        $data['currency_unit'] = isset($data['monthly_fee']['unit']) ? $data['monthly_fee']['unit'] : null;
        $data['year_1_promo']  = isset($product->price->year_1_promo) ? $product->price->year_1_promo : null;
        $data['commission']    = $product->commission;
        //break price into chunks like price, cents and currency
        $monthlyPrice    = isset($data['monthly_fee']['value']) ? $data['monthly_fee']['value'] : null;
        $monthlyPriceArr = $monthlyPrice ? explode(".", $monthlyPrice) : [];
        if (!empty($monthlyPriceArr) && !isset($monthlyPriceArr[1])) {
            $monthlyPriceArr[1] = 0;
        }
        $data['monthly_price_chunk'] = !empty($monthlyPriceArr) ? [
            'price' => $monthlyPriceArr[0],
            'cents' => ($monthlyPriceArr[1] < 10 ? '0' . $monthlyPriceArr[1] : $monthlyPriceArr[1]),
            'unit'  => isset($data['monthly_fee']['unit']) ? $data['monthly_fee']['unit'] : null,
        ] : [];

        $data['monthly_promo']          = isset($product->price->monthly_promo) ? $product->price->monthly_promo : 0;
        $data['monthly_promo_duration'] = isset($product->price->monthly_promo_duration) ? $product->price->monthly_promo_duration : 0;

        //in case normal price and promo price are not same
        $priceMonthly = isset($product->price->monthly) ? $product->price->monthly_promo : 0;
        if ($data['monthly_promo'] != $priceMonthly) {
            //break price into chunks like price, cents and currency
            $monthlyPricePromo    = $data['monthly_promo'];
            $monthlyPricePromoArr = explode(".", $monthlyPricePromo);

            if (!isset($monthlyPricePromoArr[1])) {
                $monthlyPricePromoArr[1] = 0;
            }
            $data['monthly_promo_price_chunk'] = [
                'price'    => $monthlyPricePromoArr[0],
                'cents'    => ($monthlyPricePromoArr[1] < 10 ? '0' . $monthlyPricePromoArr[1] : $monthlyPriceArr[1]),
                'unit'     => $data['monthly_price_chunk']['unit'],//use unit of normal monthly price
                'duration' => $data['monthly_promo_duration'],
            ];
        }

        $data['services'] = (array)$product->supplier->services;
        $data['logo']     = (array)$product->supplier->logo;
        $data['score']    = convertToSiteScore($product->reviews->score);
        $promotions       = (array)$product->promotions;
        foreach ($promotions as $promotion) {
            $data['promotions'][] = $promotion->texts->name;
        }

        return $data;
    }

    /**
     * This method is same as getProductPriceBreakdownHtmlApi, but it'll generate HTML in a different organized manner which is more readable,
     * another difference is it'll generate first product by default and loop over the child products inside that to display them in a specific place
     *
     * @param array $apiParams these will be API Params
     * @param bool  $enableCache
     * @param int   $expiresIn
     * @return array
     */
    public function getPbsOrganizedHtmlApi(array $apiParams, $enableCache = true, $expiresIn = 84600)
    {
        if (defined('PBS_API_CACHE_DURATION')) {
            $expiresIn = PBS_API_CACHE_DURATION;
        }
        //if language is missing get that automatically
        if (!isset($apiParams['lang_mod']) || empty($apiParams['lang_mod'])) {
            /** @var \AnbSearch\AnbCompare $anbComp */
            $anbComp               = wpal_create_instance(\AnbSearch\AnbCompare::class);
            $apiParams['lang_mod'] = $anbComp->getCurrentLang();
        }

        $apiParams['opt']       = array_filter(is_array($apiParams['opt']) ? $apiParams['opt'] : array());
        $apiParams['extra_pid'] = array_filter(is_array($apiParams['extra_pid']) ? $apiParams['extra_pid'] : array());

        $params['opt']  = array_filter(isset($apiParams['opt']) && is_array($apiParams['opt']) ? $apiParams['opt'] : array());
        $params['prt']  = isset($apiParams['prt']) ? $apiParams['prt'] : null;
        $params['a']    = '1';
        $params['pid']  = isset($apiParams['pid']) ? $apiParams['pid'] : null;
        $params['lang'] = getLanguage();

        $cacheKey = md5(serialize($params)) . ":rpc_pbs";

        if ($enableCache && !isset($_GET['no_cache'])) {
            $apiRes = mycache_get($cacheKey);

            if ($apiRes === false || empty($apiRes)) {
                $apiRes = $this->anbApi->telecomPbsRpcCall($params);
                mycache_set($cacheKey, $apiRes, $expiresIn);
            }
        } else {
            $apiRes = $this->anbApi->telecomPbsRpcCall($params);
        }

        $totalMonthly  = '';
        $totalYearly   = '';
        $totalAdvPrice = 0;
        $grandTotal    = 0;
        $productCount  = 0;
        $monthlyTotal  = 0;
        $oneoffTotal   = 0;
        $oneoffDisc   = 0;
        $yearlyTotal   = 0;
        $advTotal      = 0;

        $monthlyDisc  = 0;
        $yearlyDisc   = 0;
        $currencyUnit = '';

        if ($apiRes) {
            $apiRes = json_decode($apiRes);

            foreach ($apiRes as $key => $priceSec) {
                $currencyUnit  = $priceSec->total->unit;
                $totalMonthly  = $priceSec->monthly_costs->subtotal->display_value;
                $totalYearly   = $priceSec->total->display_value;
                $totalAdvPrice = $priceSec->total_discount->value;
                $monthlyTotal  += $priceSec->monthly_costs->subtotal->value;
                $monthlyDisc   += abs($priceSec->monthly_costs->subtotal_discount->value);

                //A patch added to negate the installation price from the total until we request it is requested, once that's addressed in API we need to revert it back
                //Revert back to the code this code $oneoffTotal   += $priceSec->oneoff_costs->subtotal->value;
                if (isset($priceSec->oneoff_costs)) {
                    if (empty($apiParams['it']) && isset($priceSec->oneoff_costs->lines->free_install->product->value)) {//don't exclude the installation if free installation option is available
                        $oneoffTotal += $priceSec->oneoff_costs->subtotal->value - ($priceSec->oneoff_costs->lines->installation->product->value + $priceSec->oneoff_costs->lines->free_install->product->value);
                    } else {
                        $oneoffTotal += $priceSec->oneoff_costs->subtotal->value;
                    }
                }

                $oneoffDisc  += isset($priceSec->oneoff_costs->subtotal_discount->value) ? abs($priceSec->oneoff_costs->subtotal_discount->value) : 0;
                $yearlyTotal += $priceSec->total->value;
                $yearlyDisc  += abs($priceSec->total_discount->value);//if number is negative convert that to +ve
                $grandTotal  += $priceSec->monthly_costs->subtotal->value;
                $advTotal    += abs($priceSec->total_discount->value);

                $productCount++;
            }
        }

        return [
            'monthly'               => $totalMonthly,
            'first_year'            => $totalYearly,
            'grand_total'           => $grandTotal,
            'yearly_total'          => $yearlyTotal,
            'monthly_total'         => $monthlyTotal,
            'monthly_disc'          => $monthlyDisc,
            'yearly_disc'           => $yearlyDisc,
            'currency_unit'         => $currencyUnit,
            'price_sections'        => $apiRes,
            'total_adv'             => $totalAdvPrice
        ];
    }

    function getToCartAnchorHtml($parentSegment, $productId, $supplierId, $sg = '', $productType = '', $forceCheckAvailability = false)
    {

        $domain          = explode('//', WP_HOME)[1];
        $directLandOrExt = (strpos($_SERVER['HTTP_REFERER'], $domain) === false || empty($_SESSION['product']['zip'])) ? true : false;

        $checkoutPageLink = '/' . ltrim($parentSegment, '/') . '/' . pll__('checkout');
        $toCartLinkHtml   = "href='" . $checkoutPageLink . "?product_to_cart&product_id=" . $productId .
            "&provider_id=" . $supplierId . "&sg=$sg&producttype=$productType'";

        if (($directLandOrExt && !isset($_GET['zip']) && empty($_GET['zip'])) || $forceCheckAvailability) {
            $toCartLinkHtml = 'data-pid="' . $productId . '" data-sid="' . $supplierId . '" data-sg="' . $sg . '" data-prt="' . $productType . '"';
        }

        $toCartInternalLink = $toCartLinkHtml;
        $justCartLinkHtml   = '<a ' . $toCartLinkHtml . ' class="btn btn-default all-caps">' . pll__('configure your pack') . '</a>';
        $oldCartLinkHtml    = '<a ' . $toCartLinkHtml . ' class="btn btn-default all-caps">' . pll__('configure your pack') . '</a>';
        $toCartLinkHtml     = '<div class="buttonWrapper print-hide">' . $justCartLinkHtml . '</div>';

        return [$toCartLinkHtml, $directLandOrExt, $justCartLinkHtml, $oldCartLinkHtml, $toCartInternalLink, $checkoutPageLink];
    }


    /**
     * Wrapper for Aanbieders API getProducts method
     *
     * @param array            $params
     * @param array|int|string $productId
     *
     * @return string
     */
    public function getProducts(array $params, $productId = null, $enableCache = true, $cacheDurationSeconds = 600)
    {
        if (defined('PRODUCT_API_CACHE_DURATION')) {
            $cacheDurationSeconds = PRODUCT_API_CACHE_DURATION;
        }

        $params['indv_product_id'] = $productId;

        $matchSlug = false;//To make sure on product detail page we don't get the wrong product from cache at all
        $slug      = '';

        if (is_string($productId) && !is_numeric($productId)) {
            //make it part of params instead of passing directly to the API
            $params['productid'] = $productId;
            $slug                = $productId;
            $productId           = null;
            $matchSlug           = true;
        }

        if ($enableCache && !isset($_GET['no_cache'])) {
            $keyParams = $params;
            $cacheKey = md5(serialize($keyParams) . $productId) . ":getProducts";
            $result = mycache_get($cacheKey);

            if (($result === false || empty($result)) ||
                ($matchSlug && !empty($result) && json_decode($result)[0]->product_slug != $slug)) {
                $result = $this->anbApi->getProducts($params, $productId);
                mycache_set($cacheKey, $result, $cacheDurationSeconds);
            }
        } else {
            $result = $this->anbApi->getProducts($params, $productId);
        }

        return $result;
    }

    function getProductsLastUpdated($lang, $productId = '', $cat = '', $enableCache = true, $cacheDurationSeconds = 42300)
    {
        $params['lang'] = $lang;
        if (!empty($productId)) {
            $params['product_id'] = $productId;
        }
        if (!empty($cat)) {
            $params['cat'] = $cat;
        }
        if (defined('HALF_DAY_CACHE_DURATION')) {
            $cacheDurationSeconds = HALF_DAY_CACHE_DURATION;
        }
        $result      = null;
        $start       = getStartTime();
        $displayText = "Time API (Previous Compare) inside getProductsLastUpdated";
        if ($enableCache && !isset($_GET['no_cache'])) {
            $cacheKey = md5("product_last_updated_$lang") . ":last_udpated_$productId";
            $result   = mycache_get($cacheKey);
            if ($result === false || empty($result)) {
                $result = $this->anbApi->getProductsLastUpdated($params);
                mycache_set($cacheKey, $result, $cacheDurationSeconds);
            } else {
                $displayText = "Time API Cached (Compare) inside getProductsLastUpdated";
            }
        } else {
            $result = $this->anbApi->getProductsLastUpdated($params);
        }
        $finish = getEndTime();
        displayCallTime($start, $finish, $displayText);

        return str_replace('"', '', $result);
    }


    /**
     * @param $array
     * @param $key
     * @param $value
     * @return array
     */
    private function searchMultidimensional($array, $key, $value)
    {
        if (is_object($array)) {
            $array = json_decode(json_encode($array), true);
        }

        $results = array();

        if (is_array($array)) {
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }

            foreach ($array as $subArray) {
                $results = array_merge($results, searchMultidimensional($subArray, $key, $value));
            }
        }

        return $results;
    }

    /**
     * @param null    $options
     * @param null    $groupOptions
     * @param boolean $isRecommendedRequired
     * @return array
     */
    public function prepareProductRecommendedOptions($options = null, $groupOptions = null, $isRecommendedRequired = true)
    {

        $groupOptionsArray  = $optionsArray = [];
        $excludeFromOptions = [];
        $minFee             = 0;

        if (is_array($groupOptions)) {
            foreach ($groupOptions as $groupOption) {
                if (is_array($groupOption)) {
                    $groupOption = (object)$groupOption;
                }

                if ($groupOption->is_recommended || $isRecommendedRequired === false) {
                    $groupOptionsArray['groupOptions'][$groupOption->optiongroup_id] = [
                        'name'        => $groupOption->texts->name,
                        'description' => $groupOption->texts->description,
                        'banner'      => $groupOption->links->banner,
                    ];

                    foreach ($groupOption->options as $listOption) {
                        $optionSpecification = $this->searchMultidimensional($options, 'option_id', $listOption);
                        if ($optionSpecification) {
                            $optionSpecification = (is_array($optionSpecification)) ? array_pop($optionSpecification) : $optionSpecification;

                            if ($minFee == 0) {
                                $minFee = $optionSpecification['price'];
                            }

                            if ($optionSpecification['price'] < $minFee) {
                                $minFee = $optionSpecification['price'];
                            }

                            $excludeFromOptions[]                                                         = $optionSpecification['option_id'];
                            $groupOptionsArray['groupOptions'][$groupOption->optiongroup_id]['options'][] = [
                                'id'           => $optionSpecification['option_id'],
                                'price'        => $optionSpecification['price'],
                                'price_oneoff' => $optionSpecification['price_oneoff'],
                                'name'         => $optionSpecification['texts']['name'],
                                'description'  => $optionSpecification['texts']['description'],
                                'banner'       => $optionSpecification['links']['banner'],
                            ];
                        }
                    }
                    $groupOptionsArray['groupOptions'][$groupOption->optiongroup_id]['minPrice'] = $minFee;
                }
            }
        }

        foreach ($options as $listOption) {
            if (is_array($listOption)) {
                $listOption = (object)$listOption;
            }
            if (($listOption->is_recommended || $isRecommendedRequired === false) && !in_array($listOption->option_id, $excludeFromOptions)) {

                $optionsArray['options'][$listOption->option_id] = [
                    'id'           => $listOption->option_id,
                    'price'        => $listOption->price,
                    'price_oneoff' => $listOption->price_oneoff,
                    'name'         => $listOption->texts->name,
                    'description'  => $listOption->texts->description,
                    'banner'       => $listOption->links->banner,
                ];
            }

        }

        return array_merge($groupOptionsArray, $optionsArray);
    }

    /**
     * @param $lineVal
     * @param $oldPriceHtml
     * @param $hasOldPriceClass
     * @param $promoPriceHtml
     *
     * @return string
     */
    public function generatePbsPackOptionHtml($lineVal, $oldPriceHtml, $hasOldPriceClass, $promoPriceHtml, $offerPrice)
    {
        $priceText = '';
        if (!is_numeric($lineVal->product->value)) {
            $priceText = ucfirst($lineVal->product->value);
        }
        $priceArr = formatPriceInParts($lineVal->product->value - $offerPrice, 2, $lineVal->product->unit);

        if ($offerPrice == 0) {
            $oldPriceHtml = '';
        }

        $currPriceHtml = '<span class="currentPrice ident-applied-price" applied-price="' . ($lineVal->product->value - $offerPrice) . '">
					                <span class="currency">' . $priceArr['currency'] . '</span>
					                <span class="amount">' . $priceArr['price'] . '</span>
					                <span class="cents">' . $priceArr['cents'] . '</span>
					            </span>';

        if ($priceText) {
            $currPriceHtml = '<span class="currentPrice">' . $priceText . '</span>';
        }

        $currOldPriceHtml = '<div class="packagePrice">
				            ' . $oldPriceHtml . $currPriceHtml . '
				            </div>';

        $instClass = '';

        //Special class to identfy the installation location, to overcome a bug in the API that doesn't gives back the correct DIY price
        if (strpos(strtolower($lineVal->label), 'inst') !== false) {
            $instClass = 'ident-inst';
        }

        $priceDetailHtml = '<li class="packOption">
								<div class="packageDetail ' . $instClass . '">
								<div class="packageDesc ' . $hasOldPriceClass . '">' . $lineVal->label . '</div>
					            ' . $currOldPriceHtml . $promoPriceHtml . '
					            </div>
					            </li>';

        return $priceDetailHtml;
    }
}
