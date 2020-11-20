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

    function getActivationOrInstPriceHtml($priceDetailArray, $key, $currencySymbol = '', $onlyArray = false, $withFirstTerm = true, &$totalOnetimeAmount = 0.00)
    {
        //translations in function: pll__('Free installation'), pll__('Free activation'), pll__('Installation'), pll__('t.w.v')
        $html         = '';
        $firstTerm    = explode('_', $key)[0];//first term before underscore like installation from installation_full
        $firstTermLbl = ucfirst($firstTerm);

        if ($onlyArray) {
            $prices                   = [];
            $prices[$key . '_actual'] = $priceDetailArray[$key];
            if ($priceDetailArray[$key] > 0) {
                if ($priceDetailArray[$key . '_promo'] > 0
                    && $priceDetailArray[$key . '_promo'] != $priceDetailArray[$key]
                ) {//there is a promotional price as well

                    $prices[$key]            = $priceDetailArray[$key];
                    $prices[$key . '_promo'] = $priceDetailArray[$key . '_promo'];

                } elseif ($priceDetailArray[$key . '_promo'] == 0) {
                    if ($withFirstTerm) {
                        $prices[$key] = pll__('Free');
                    } else {
                        $prices[$key] = pll__('Free' . (!empty($firstTerm)) ? ' ' . $firstTerm : '');
                    }
                    $prices[$key . '_free'] = true;
                } else {
                    $prices[$key] = round($priceDetailArray[$key]);
                }
            } else {
                if ($withFirstTerm) {
                    $prices[$key] = pll__('Free');
                } else {
                    $prices[$key] = pll__('Free' . (!empty($firstTerm)) ? ' ' . $firstTerm : '');
                }
                $prices[$key . '_free'] = true;
            }

            return $prices;
        }

        //display installation and activation price
        if ($priceDetailArray[$key] > 0) {
            $oldPriceArr     = formatPriceInParts($priceDetailArray[$key], 2, $currencySymbol);
            $oldPriceHtml    = '<span class="cutPrice"><span class="amount">' . $oldPriceArr['currency'] . $oldPriceArr['price'] . '</span><span class="cents">' . $oldPriceArr['cents'] . '</span></span>';
            $actualPriceArr  = formatPriceInParts($priceDetailArray[$key], 2, $currencySymbol);
            $actualPriceHtml = '<span class="bold"><span class="amount">' . $actualPriceArr['currency'] . $actualPriceArr['price'] . '</span><span class="cents">' . $actualPriceArr['cents'] . '</span></span>';
            $promoPriceArr   = formatPriceInParts($priceDetailArray[$key . '_promo'], 2, $currencySymbol);
            $promoPriceHtml  = '<span class="bold"><span class="amount">' . $promoPriceArr['currency'] . $promoPriceArr['price'] . '</span><span class="cents">' . $promoPriceArr['cents'] . '</span></span>';
            if ($priceDetailArray[$key . '_promo'] > 0
                && $priceDetailArray[$key . '_promo'] != $priceDetailArray[$key]
            ) {//there is a promotional price as well
                $totalOnetimeAmount += $priceDetailArray[$key . '_promo'];
                $html               .= '<li class="prominent">' . pll__($firstTermLbl) . ' ' . $promoPriceHtml .
                    ' ' . $oldPriceHtml . '</li>';
            } elseif ($priceDetailArray[$key . '_promo'] == 0) {
                $html .= '<li class="prominent">' . pll__('Free ' . $firstTerm) . ' ' . $oldPriceHtml . '</li>';
            } else {
                $totalOnetimeAmount += $priceDetailArray[$key];
                $html               .= '<li class="bulletTick">' . pll__($firstTermLbl) . ' ' . $actualPriceHtml . '</li>';
            }
        } else {
            $html .= '<li class="prominent">' . pll__('Free ' . $firstTerm) . '</li>';
        }

        return $html;
    }

    function getActOrInstPriceBreakDownHtml($priceArray, $key, $currencySymbol = '')
    {
        if (empty($priceArray[$key])) {
            return '';
        }

        $firstTerm = explode('_', $key)[0];//first term before underscore like installation from installation_full

        $promoPriceHtml = (!empty($priceArray[$key . '_promo']) && $priceArray[$key . '_promo'] != $priceArray[$key . '_actual']) ? '<span class="saving-price">' . formatPrice($priceArray[$key], 2, $priceArray[$key . '_actual']) . '</span>' : '';
        $html           = '<li>' . pll__(ucfirst($firstTerm) . ' cost') . '
					' . $promoPriceHtml . '
                    <span class="cost-price">' . formatPrice($priceArray[$key], 2, $currencySymbol) . '</span>
                 </li>';

        if ($priceArray[$key . '_free'] === true) {
            $html = '<li class="prominent">' . pll__(ucfirst($firstTerm) . ' cost') . '<span class="cost-price">' . pll__('Free') . '</span></li>';
        }

        return $html;
    }

    /**
     * @param array $prd
     *
     * @return string
     */
    function getServicesHtml(array $prd)
    {
        $servicesHtml = '';
        //$prd['packtype']: This is combining mulitple names into one using + sign e.g. Internet + TV
        $prdOrPckTypes = ($prd['producttype'] == 'packs') ? $prd['packtypes'] : $prd['producttype'];
        $prdOrPckTypes = (!is_array($prdOrPckTypes)) ? strtolower($prdOrPckTypes) : $prdOrPckTypes;

        if ((is_array($prdOrPckTypes) && in_array('internet', $prdOrPckTypes)) ||
            (!is_array($prdOrPckTypes) && strpos($prdOrPckTypes, "int") !== false && $prdOrPckTypes != 'mobile_internet')) {
            $servicesHtml .= '<li class="wifi">
                                <i class="service-icons wifi print-hide"></i>
                                <img src="' . get_bloginfo('template_url') . '/images/print-images/internet.svg" alt="" class="print-show" />
                              </li>';
        }
        if ((is_array($prdOrPckTypes) && in_array('mobile', $prdOrPckTypes)) ||
            (!is_array($prdOrPckTypes) && (strpos($prdOrPckTypes, "mobile") !== false
                    || strpos($prdOrPckTypes, "gsm") !== false) && $prdOrPckTypes != 'mobile_internet')) {
            $servicesHtml .= '<li class="mobile">
                                <i class="service-icons mobile print-hide"></i>
                                <img src="' . get_bloginfo('template_url') . '/images/print-images/mobile.svg" alt="" class="print-show" />
                              </li>';
        }
        if ((is_array($prdOrPckTypes) && in_array('telephony', $prdOrPckTypes)) ||
            (!is_array($prdOrPckTypes) && strpos($prdOrPckTypes, "tel") !== false)) {
            $servicesHtml .= '<li class="phone">
                                <i class="service-icons phone print-hide"></i>
                                <img src="' . get_bloginfo('template_url') . '/images/print-images/telephony.svg" alt="" class="print-show" />
                              </li>';
        }
        if ((is_array($prdOrPckTypes) && in_array('idtv', $prdOrPckTypes)) ||
            (!is_array($prdOrPckTypes) && strpos($prdOrPckTypes, "tv") !== false)) {
            $servicesHtml .= '<li class="tv">
                                <i class="service-icons tv print-hide"></i>
                                <img src="' . get_bloginfo('template_url') . '/images/print-images/idtv.svg" alt="" class="print-show" />
                              </li>';
        }
        if ((is_array($prdOrPckTypes) && in_array('mobile_internet', $prdOrPckTypes)) ||
            (!is_array($prdOrPckTypes) && strpos($prdOrPckTypes, "mobile_internet") !== false)) {
            $servicesHtml .= '<li class="mobile_internet">                              
                                <img src="' . get_bloginfo('template_url') . '/images/print-images/mobile-data-sim.svg" alt="" />
                              </li>';
        }

        return $servicesHtml;
    }

    /**
     * @param array $products
     *
     * @return array
     */
    public function prepareProductsData(array $products)
    {
        $data = [];
        //for now hardcoded decimal separator to coma
        foreach ($products as $idx => $product) {
            $data[$idx] = $this->prepareProductData($product);
        }

        return $data;
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
     * @param array $prd
     *
     * @return string
     */
    public function getPriceHtml(array $prd, $withCalcHtml = false)
    {
        $priceHtml = '';
        $calcHtml  = '';

        if ($withCalcHtml) {
            $href = "action=ajaxProductPriceBreakdownHtml&pid={$prd['product_id']}&prt={$prd['producttype']}";

            $calcHtml = '<span class="calc">
                    <a href="' . $href . '" data-toggle="modal" data-target="#calcPbsModal">
                        <i class="custom-icons calc"></i>
                    </a>
                 </span>';
        }

        if ($prd['monthly_price_chunk']['cents'] == "000") {
            $prd['monthly_price_chunk']['cents'] = "00";
        }

        if (isset($prd['monthly_promo_price_chunk'])) {
            $priceHtml .= '<div class="oldPrice">
                                <span class="amount">' . getCurrencySymbol($prd['currency_unit']) . $prd['monthly_price_chunk']['price'] . '</span>';
            if (isset($prd['monthly_price_chunk']['cents']) && !empty($prd['monthly_price_chunk']['cents'])) {
                $priceHtml .= '<span class="cents">' . substr($prd['monthly_price_chunk']['cents'], 0, 2) . '</span>';
            }
            $priceHtml .= '</div>';

            $priceHtml .= '<div class="newPrice">
                                <span class="amount">' . $prd['monthly_promo_price_chunk']['price'];
            if (isset($prd['monthly_price_chunk']['cents']) && !empty($prd['monthly_price_chunk']['cents'])) {
                $priceHtml .= '<span class="cents">' . substr($prd['monthly_promo_price_chunk']['cents'], 0, 2) . '</span>';
            }
            $priceHtml .= '<span class="recursion">/' . pll__('mth') . '</span>
						   ' . $calcHtml . '
						</span>
                       </div>';
        } else {
            $priceHtml .= '<div class="oldPrice"></div>';
            $priceHtml .= '<div class="newPrice">
                                <span class="amount">' . $prd['monthly_price_chunk']['price'];
            if (isset($prd['monthly_price_chunk']['cents']) && !empty($prd['monthly_price_chunk']['cents'])) {
                $priceHtml .= '<span class="cents">' . substr($prd['monthly_price_chunk']['cents'], 0, 2) . '</span>';
            }
            $priceHtml .= '<span class="recursion">/' . pll__('mth') . '</span>
						   ' . $calcHtml . '
						</span>
                       </div>';
        }

        return $priceHtml;
    }

    /**
     * @param array $prd
     *
     * @return string
     */
    public function getPromoHtml(array $prd)
    {
        //display promotions
        $promotionHtml = '';
        foreach ($prd['promotions'] as $promotion) {
            $promotionHtml .= '<li class="prominent">' . $promotion . '</li>';
        }

        return $promotionHtml;
    }

    /**
     * @param array   $prd
     * @param boolean $withoutPromoList
     *
     * @return string
     */
    public function getPromoInternalSection(array $prd, $withoutPromoList = false, $withTotalOnetimeCost = false)
    {
        $promotionHtml      = '';
        $totalOnetimeAmount = 0;
        if (!in_array($prd['producttype'], $this->producttypesToSkipPromos)) {
            $promotionHtml .= $this->getActivationOrInstPriceHtml($prd['price'], 'installation_full', getCurrencySymbol($prd['currency_unit']), false, true, $totalOnetimeAmount);
            $promotionHtml .= $this->getActivationOrInstPriceHtml($prd['price'], 'activation', getCurrencySymbol($prd['currency_unit']), false, true, $totalOnetimeAmount);
            $promotionHtml .= $this->getActivationOrInstPriceHtml($prd['price'], 'modem', getCurrencySymbol($prd['currency_unit']), false, true, $totalOnetimeAmount);
        }

        //will block other promtions except from one-off costs like installation and activation
        if (!$withoutPromoList) {
            $promotionHtml .= $this->getPromoHtml($prd);
        }

        return ($withTotalOnetimeCost) ? [$promotionHtml, $totalOnetimeAmount] : $promotionHtml;
    }

    public function getServiceIconsSection($servicesHtml)
    {
        $serviceSec = '<div class="services">
                            <ul class="list-unstyled list-inline">
                                ' . $servicesHtml . '
                            </ul>
                         </div>';

        return $serviceSec;
    }

    public function getPromoSection($promotionHtml, $advPrice, $cssClass = 'dealFeatures', $appendHtml = '', $withOnetimeCostLabel = false, $oneTimeTotalCost = 0)
    {
        $oneTimeCostLabel = '';
        if ($withOnetimeCostLabel) {
            $oneTimeCostLabel = '<h6>' . pll__('One-time costs') . '</h6>';
        }
        $advHtml = '';
        if (is_numeric($advPrice) && $advPrice > 0) {

            $advHtml = $this->getTotalAdvHtml($advPrice);
        }

        if (!$promotionHtml) {
            return '';
        }
        $promoSec = '<div class="' . $cssClass . '">
                        <div class="extras">
                        	' . $oneTimeCostLabel . '
                            <ul class="list-unstyled">
                                ' . $promotionHtml . '
                            </ul>
                        </div>
                        ' . $advHtml . '
                        ' . $appendHtml . '
                    </div>';

        return $promoSec;
    }

    public function priceSection($priceHtml, $monthDurationPromo, $firstYearPrice, $cssClass = 'dealPrice', $appendHtml = '', $calcHtml = '', $productData = [], $withoutYearlyCost = false)
    {
        $prominentClass = '';
        if ($firstYearPrice) {
            $prominentClass = 'class="prominent"';
        }

        $yearlyPriceHtml = '';
        if ($withoutYearlyCost) {
            $promoPrice = $productData['price']['monthly'] - $productData['price']['monthly_promo'];
            if ($productData['monthly_promo_duration'] > 0 && $promoPrice > 0) {
                $formatedPromoPrice = getCurrencySymbol($productData['currency_unit']) . ' ' . formatPrice($promoPrice, 2, '');
                $yearlyPriceHtml    = "<li $prominentClass>" . sprintf(pll__('%s discount for %d months'), $formatedPromoPrice, $productData['monthly_promo_duration']) . "</li>";
            } else {
                $yearlyPriceHtml = "<li></li>";
            }
        } else {
            $yearlyPriceHtml = '<li>' . $monthDurationPromo . '</li>
                                <li ' . $prominentClass . '>' . $firstYearPrice
                . $calcHtml .
                '</li>';
        }

        if (!empty($firstYearPrice) || !empty($monthDurationPromo) || !empty($calcHtml)) {
            $priceInfoHtml = '<div class="priceInfo">
                            <ul class="list-unstyled">
                                ' . $yearlyPriceHtml . '
                            </ul>
                        </div>';
        }

        $priceSec = '<div class="' . $cssClass . '">
                        ' . $priceHtml . '
                        ' . $priceInfoHtml . '
                        ' . html_entity_decode($appendHtml) . '
                     </div>';

        return $priceSec;
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

        $params['opt']  = array_filter(is_array($apiParams['opt']) ? $apiParams['opt'] : array());
        $params['prt']  = $apiParams['prt'];
        $params['a']    = '1';
        $params['pid']  = $apiParams['pid'];
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
                if (empty($apiParams['it']) && !isset($priceSec->oneoff_costs->lines->free_install->product->value)) {//don't exclude the installation if free installation option is available
                    $oneoffTotal += $priceSec->oneoff_costs->subtotal->value - ($priceSec->oneoff_costs->lines->installation->product->value + $priceSec->oneoff_costs->lines->free_install->product->value);
                } else {
                    $oneoffTotal += $priceSec->oneoff_costs->subtotal->value;
                }

                $oneoffDisc  += abs($priceSec->oneoff_costs->subtotal_discount->value);
                $yearlyTotal += $priceSec->total->value;
                $yearlyDisc  += abs($priceSec->total_discount);//if number is negative convert that to +ve
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
     * @param array $prd
     *
     * @return string
     */
    public function getTitleSection(array $prd, $listView = false)
    {

        $titleSec = '<h4>' . $prd['product_name'] . '</h4>
                     <p class="slogan">' . $prd['tagline'] . '</p>';

        if ($listView) {
            $titleSec = '<h5>' . $prd['product_name'] . '</h5>';
        }

        return $titleSec;
    }

    public function getLogoSection(array $prd, $listview, $includeText)
    {
        $greyClass = '';
        if ($includeText) {
            $greyClass = 'partnergrey';
        }
        $logoSec = '<div class="dealLogo">
                        <img class="' . $greyClass . '" src="' . $prd['logo']['200x140']->transparent->color . '" alt="' . $prd['product_name'] . '">
                    </div>';
        return $logoSec;
    }

    public function getCustomerRatingSection($prd, $listView = false)
    {
        $custRatSec = '';
        if ((float)$prd['score'] > 0) {
            $custRatSec = '<div class="customerRating">
                            <div class="stamp">
                                ' . $prd['score'] . '
                            </div>
                            <span>' . pll__('Customer Score') . '</span>
                       </div>';

            if ($listView) {
                $custRatSec = '<div class="recCustomerRating">
	                                <span class="ratingCount">' . $prd['score'] . '</span>
	                                <span class="labelHolder">' . pll__('Customer Score') . '</span>
	                            </div>';
            }
        }

        return $custRatSec;
    }

    /**
     * @param string $badgeTxt
     *
     * @return string
     */
    public function getBadgeSection($badgeTxt)
    {
        $revSec = '<div class="bestReviewBadge">
                        <span>' . pll__('BEST') . '</span>
                        <span class="bold">' . pll__($badgeTxt) . '</span>
                   </div>';

        return $revSec;
    }

    public function getProductDetailSection($prd, $servicesHtml, $includeText = false, $includeBadge = false, $badgeTxt = '', $listView = false)
    {
        $detailsSec = '<div class="dealDetails">';

        if ($includeBadge && !empty($badgeTxt)) {
            $detailsSec .= $this->getBadgeSection($badgeTxt);
        }

        if ($listView) {
            $detailsSec .= $this->getLogoSection($prd, $listView, $includeText) .
                $this->getTitleSection($prd, $listView) .
                $this->getCustomerRatingSection($prd, $listView);
        } else {
            $detailsSec .= $this->getLogoSection($prd, $listView, $includeText) .
                $this->getTitleSection($prd) .
                $this->getServiceIconsSection($servicesHtml) .
                $this->getCustomerRatingSection($prd);
        }

        $detailsSec .= '</div>';

        return $detailsSec;
    }

    /**
     * @param array   $prd
     * @param boolean $onlyNumericData
     *
     * @return array
     */
    public function getPriceInfo(array $prd, $onlyNumericData = false)
    {
        $advPrice = '&nbsp;';
        $totalAdv = 0;

        if (!empty($prd['advantage']) && $prd['advantage'] > 0) {//only include +ve values in advantage
            if ($onlyNumericData) {
                $advPrice = $prd['advantage'];
            } else {
                $advPrice = formatPrice($prd['advantage'], 2, getCurrencySymbol($prd['currency_unit'])) . ' ' . pll__('advantage');
            }
            $totalAdv = $prd['advantage'];
        }

        $monthDurationPromo = '&nbsp;';
        //sprintf
        if (!empty($prd['monthly_promo_duration'])) {
            if ($onlyNumericData) {
                $monthDurationPromo = $prd['monthly_promo_duration'];
            } else {
                $monthDurationPromo = sprintf(pll__('the first %d months'), $prd['monthly_promo_duration']);
            }
        }

        $firstYearPrice = '';
        if (intval($prd['year_1_promo']) > 0) {
            if ($onlyNumericData) {
                $firstYearPrice = $prd['year_1_promo'];
            } else {
                $firstYearPrice = getCurrencySymbol($prd['currency_unit']) . ' ' . formatPrice(intval($prd['year_1_promo']), 0, '');
                $firstYearPrice = $firstYearPrice . ' ' . pll__('the first year');
            }
        }

        return array($advPrice, $monthDurationPromo, $firstYearPrice, $totalAdv);
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

    /**
     * @param $advPrice
     *
     * @return string
     */
    public function getTotalAdvHtml($advPrice)
    {
        if ($advPrice['price'] == 0) {
            return '';
        }

        $advPriceArr = formatPriceInParts($advPrice, 2);
        $advHtml     = '<div class="calcPanelTotal blue">
                            <div class="packageTotal">
                                <span class="caption">' . pll__('Your advantage') . '</span>
                                <span class="price">
                                <span class="currency">' . $advPriceArr['currency'] . '</span>
                                <span class="amount">' . $advPriceArr['price'] . '</span>
                                <span class="cents">' . $advPriceArr['cents'] . '</span>
                            </span>
                            </div>
                        </div>';

        return $advHtml;
    }

}
