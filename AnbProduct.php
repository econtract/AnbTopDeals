<?php
/**
 * Created by PhpStorm.
 * User: imran
 * Date: 5/12/17
 * Time: 6:02 PM
 */

namespace AnbTopDeals;

use AnbApiClient\Aanbieders;

class AnbProduct
{

    public $crmApiEndpoint = "http://api.econtract.be/";//Better to take it from Admin settings
    public $anbApi;
    public $apiConf = [
        'staging' => ANB_API_STAGING,
        'key' => ANB_API_KEY,
        'secret' => ANB_API_SECRET
    ];

    public function __construct()
    {
        $this->anbApi = wpal_create_instance(Aanbieders::class, [$this->apiConf]);
    }

    function topDealProducts($atts, $nav = "")
    {
        $atts = shortcode_atts(array(
            'cat' => '',
            'detaillevel' => ['supplier', 'logo', 'services', 'price', 'reviews', 'texts', 'promotions'],//specifications, logo
            'sg' => 'consumer',
            'product_1' => [],
            'product_2' => [],
            'product_3' => [],
            'lang' => 'nl',
            'is_active' => 'no',
            'is_first' => 'no'

        ), $atts, 'anb_top_deal_products');

        if (!empty($atts['detaillevel']) && is_string($atts['detaillevel'])) {
            $atts['detaillevel'] = explode(',', $atts['detaillevel']);
        }

        if (empty($atts['product_1']) || empty($atts['product_2']) || empty($atts['product_3']) || empty($nav)) {
            return;
        }

        $nav = sanitize_text_field($nav);

        pll_register_string($nav, $nav, 'AnbTopDeals');

        //remove empty params
        $params = array_filter($atts);

        // get the products
        //$products = $this->anbApi->getProducts($params, $atts['product_ids']);

        /*$products = $this->anbApi->getProducts(array('cat'=>array('dualfuel_pack', 'internet'), 'ssg'=>'consumer', 'lang'=>'nl', 'status'=>array(0,1,2),
            'productid'=>array('internet|180','dualfuel_pack|11', 'dualfuel_pack|18'),
            'detaillevel'=>array('ddspecifications'), 'a'=>31));*/

        //Extract categories from each product
        $cats = [];
        $cats[] = substr($atts['product_1'], 0, strpos($atts['product_1'], "|"));
        $cats[] = substr($atts['product_2'], 0, strpos($atts['product_2'], "|"));
        $cats[] = substr($atts['product_3'], 0, strpos($atts['product_3'], "|"));

        $cats = array_unique($cats);

        $products = $this->anbApi->getProducts(array('cat' => $cats, 'sg' => $atts['sg'], 'lang' => $atts['lang'],
            'productid' => array($atts['product_1'], $atts['product_2'], $atts['product_3']),
            'detaillevel' => $atts['detaillevel']));

        $products = json_decode($products);

        /*echo "<pre>PRODUCTS>>>";
        print_r($products);
        echo "</pre>";*/

        //prepare product data to be displayed
        $data = $this->prepareProductsData($products);

        /*echo "<pre>DATA>>>";
        print_r($data);
        echo "</pre>";*/

        wp_enqueue_script('jquery');
        wp_enqueue_script('top_deals_js', plugin_dir_url(__FILE__) . 'js/top-deals.js');

        $htmlWrapper = '';
        if ($atts['is_first'] == 'yes') {
            $htmlWrapper = '<section class="topDeals">
                        <div class="container">
                            <div class="topDealsWrapper">
                                <h3>' . pll__('Proximus most popular') . '</h3>
                                <div class="filterDeals">
                                    <ul class="list-unstyled list-inline">
                                    </ul>
                                </div>
                                <div class="dealsTable">
                                    
                                </div>
                            </div>
                        </div>
                     </section>';
        }

        echo $htmlWrapper;

        //append Navigation to the HTML
        $class = '';
        $displayStyle = '';
        if ($atts['is_active'] == 'yes') {
            $class = 'class="active"';
        } else {
            $displayStyle = 'style="display:none;"';
        }

        $navHtmlName = sanitize_title_with_dashes($nav);
        $navContent = '<div class="row ' . $navHtmlName . '" ' . $displayStyle . '>';
        foreach ($data as $idx => $prd) {
            $boxClass = 'left';
            if ($idx == 1) {
                $boxClass = 'center';
            } elseif ($idx == 2) {
                $boxClass = 'right';
            }

            //Services HTML
            $servicesHtml = $this->getServicesHtml($prd);

            //Price HTML
            $priceHtml = $this->getPriceHtml($prd);

            //Promotions, Installation/Activation HTML
            //display installation and activation price
            $promotionHtml = $this->getPromoInternalSection($prd);


            list($advPrice, $monthDurationPromo, $firstYearPrice) = $this->getPriceInfo($prd);

            $navContent .= '<div class="col-md-4 offer ' . $boxClass . '">
                                ' . $this->getProductDetailSection($prd, $servicesHtml) .
                $this->priceSection($priceHtml, $monthDurationPromo, $firstYearPrice) .
                $this->getPromoSection($promotionHtml, $advPrice) . '
                                <a href="#" class="btn btn-primary">' . pll__('Info and options') . '</a>
                            </div>';
        }
        $navContent .= '</div>';

        $navHtml = '<li ' . $class . '><a href="javascript:void(0);" related="' . $navHtmlName . '">' . pll__($nav) . '</a></li>';

        //$script = '<script>appendToSelector(".topDeals .filterDeals ul", {"html": \''.$navHtml.'\'}); appendToSelector(".topDeals .dealsTable", {"html": \''.$navContent.'\'})</script>';
        $script = '<script>
                    jQuery(document).ready(function($){
                        appendToSelector(".topDeals .filterDeals ul",  \'' . $navHtml . '\'); 
                        appendToSelector(".topDeals .dealsTable", \'' . $this->minifyHtml($navContent) . '\')
                    });
                   </script>';
        echo $script;

        //return "<pre>" . print_r($params, true) . "<br><br>" . print_r($products, true) . "</pre>";
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
            ''
        );

        $buffer = preg_replace($search, $replace, $buffer);

        return $buffer;
    }

    function getActivationOrInstPriceHtml($priceDetailArray, $key, $currencySymbol)
    {
        //translations in function: pll__('Free installation'), pll__('Free activation'), pll__('Installation'), pll__('t.w.v')
        $html = '';
        $firstTerm = explode('_', $key)[0];//first term before underscore like installation from installation_full
        $firstTermLbl = ucfirst($firstTerm);
        //display installation and activation price
        if ($priceDetailArray[$key] > 0) {
            if ($priceDetailArray[$key . '_promo'] > 0
                && $priceDetailArray[$key . '_promo'] != $priceDetailArray[$key]
            ) {//there is a promotional price as well
                $html .= '<li class="prominent">' . pll__($firstTermLbl) . ' ' . $priceDetailArray[$key . '_promo'] .
                    $currencySymbol . ' ' . pll__('t.w.v') . ' ' . $priceDetailArray[$key] .
                    $currencySymbol . '</li>';
            } elseif ($priceDetailArray[$key . '_promo'] == 0) {
                $html .= '<li class="prominent">' . pll__('Free ' . $firstTerm) . ' ' . pll__('t.w.v') .
                    ' ' . $priceDetailArray[$key] . $currencySymbol . '</li>';
            } else {
                $html .= '<li>' . pll__($firstTermLbl) . ' ' . round($priceDetailArray[$key]) .
                    $currencySymbol . '</li>';
            }
        } else {
            $html .= '<li class="prominent">' . pll__('Free ' . $firstTerm) . '</li>';
        }

        return $html;
    }

    /**
     * @param array $prd
     * @return string
     */
    function getServicesHtml(array $prd)
    {
        $servicesHtml = '';

        $prdOrPckTypes = ($prd['producttype'] == 'packs') ? $prd['packtype'] : $prd['producttype'];
        $prdOrPckTypes = strtolower($prdOrPckTypes);

        if (strpos($prdOrPckTypes, "int") !== false) {
            $servicesHtml .= '<li>
                                <i class="service-icons wifi"></i>
                              </li>';
        }
        if (strpos($prdOrPckTypes, "gsm") !== false) {
            $servicesHtml .= '<li>
                                <i class="service-icons mobile"></i>
                              </li>';
        }
        if (strpos($prdOrPckTypes, "tel") !== false) {
            $servicesHtml .= '<li>
                                <i class="service-icons phone"></i>
                              </li>';
        }
        if (strpos($prdOrPckTypes, "tv") !== false) {
            $servicesHtml .= '<li>
                                <i class="service-icons tv"></i>
                              </li>';
        }

        return $servicesHtml;
    }

    /**
     * @param array $products
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
     * @return array
     */
    public function prepareProductData($product)
    {
        $data = [];
        //for now hardcoded decimal separator to coma
        //Pack type: 'int_tel', 'tv_tel', 'int_tel_tv', 'gsm_int_tel_tv', 'int_tv', 'gsm_int_tv'
        $data['producttype'] = $product->producttype;

        if ($product->producttype == "packs") {
            $data['packtype'] = $product->packtype;
        }

        $data['product_name'] = $product->product_name;
        $data['tagline'] = isset($product->texts->tagline) ? $product->texts->tagline : "";
        $data['price'] = (array)$product->price;
        $data['monthly_fee'] = (array)$product->monthly_fee;
        $data['advantage'] = $product->price->advantage;
        $data['currency_unit'] = $data['monthly_fee']['unit'];
        $data['year_1_promo'] = $product->price->year_1_promo;
        //break price into chunks like price, cents and currency
        $monthlyPrice = $data['monthly_fee']['value'];
        $monthlyPriceArr = explode(".", $monthlyPrice);
        if (!isset($monthlyPriceArr[1])) {
            $monthlyPriceArr[1] = 0;
        }
        $data['monthly_price_chunk'] = [
            'price' => $monthlyPriceArr[0],
            'cents' => ($monthlyPriceArr[1] < 10 ? '0' . $monthlyPriceArr[1] : "00"),
            'unit' => $data['monthly_fee']['unit']
        ];
//            echo "+++".print_r($product->price, true)."<br>";
        $data['monthly_promo'] = isset($product->price->monthly_promo) ? $product->price->monthly_promo : 0;
        $data['monthly_promo_duration'] = isset($product->price->monthly_promo_duration) ? $product->price->monthly_promo_duration : 0;

        //in case normal price and promo price are not same
        if ($product->price->monthly_promo != $product->price->monthly) {
            //break price into chunks like price, cents and currency
            $monthlyPricePromo = $data['monthly_promo'];
            $monthlyPricePromoArr = explode(".", $monthlyPricePromo);

            if (!isset($monthlyPricePromoArr[1])) {
                $monthlyPricePromoArr[1] = 0;
            }
            $data['monthly_promo_price_chunk'] = [
                'price' => $monthlyPricePromoArr[0],
                'cents' => ($monthlyPricePromoArr[1] < 10 ? '0' . $monthlyPricePromoArr[1] : "00"),
                'unit' => $data['monthly_price_chunk']['unit'],//use unit of normal monthly price
                'duration' => $data['monthly_promo_duration']
            ];
            //echo "+++".print_r($data['monthly_promo_price_chunk'], true)."<br>";
        }

        $data['services'] = (array)$product->supplier->services;
        $data['logo'] = (array)$product->supplier->logo;
        $data['score'] = str_replace(",", ".", $product->reviews->score);
        $promotions = (array)$product->promotions;
        foreach ($promotions as $promotion) {
            $data['promotions'][] = $promotion->texts->name;
        }
        return $data;
    }

    /**
     * @param array $prd
     * @return string
     */
    public function getPriceHtml(array $prd)
    {
        $priceHtml = '';

        if($prd['monthly_price_chunk']['cents'] == "000") {
            $prd['monthly_price_chunk']['cents'] = "00";
        }

        if (isset($prd['monthly_promo_price_chunk'])) {
            $priceHtml .= '<div class="oldPrice">
                                <span class="amount">' . $prd['monthly_price_chunk']['price'] . '</span>';
            if (isset($prd['monthly_price_chunk']['cents']) && !empty($prd['monthly_price_chunk']['cents'])) {
                $priceHtml .= '<span class="cents">' . $prd['monthly_price_chunk']['cents'] . '</span>';
            }
            $priceHtml .= '</div>';

            $priceHtml .= '<div class="newPrice">
                                <span class="amount">' . $prd['monthly_promo_price_chunk']['price'];
            if (isset($prd['monthly_price_chunk']['cents']) && !empty($prd['monthly_price_chunk']['cents'])) {
                $priceHtml .= '<span class="cents">' . $prd['monthly_promo_price_chunk']['cents'] . '</span>';
            }
            $priceHtml .= '<span class="recursion">/mth</span></span>
                               </div>';
        } else {
            $priceHtml .= '<div class="newPrice">
                                <span class="amount">' . $prd['monthly_price_chunk']['price'];
            if (isset($prd['monthly_price_chunk']['cents']) && !empty($prd['monthly_price_chunk']['cents'])) {
                $priceHtml .= '<span class="cents">' . $prd['monthly_price_chunk']['cents'] . '</span>';
            }
            $priceHtml .= '<span class="recursion">/mth</span></span>
                               </div>';
        }
        return $priceHtml;
    }

    /**
     * @param array $prd
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
     * @param array $prd
     * @param boolean $withoutPromoList
     * @return string
     */
    public function getPromoInternalSection(array $prd, $withoutPromoList = false)
    {
        $promotionHtml = '';
        $promotionHtml .= $this->getActivationOrInstPriceHtml($prd['price'], 'installation_full', getCurrencySymbol($prd['currency_unit']));
        $promotionHtml .= $this->getActivationOrInstPriceHtml($prd['price'], 'activation', getCurrencySymbol($prd['currency_unit']));
        if (!$withoutPromoList) {
            $promotionHtml .= $this->getPromoHtml($prd);
        }
        return $promotionHtml;
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

    public function getPromoSection($promotionHtml, $advPrice, $cssClass = 'dealFeatures', $appendHtml = '')
    {
        $promoSec = '<div class="' . $cssClass . '">
                        <div class="extras">
                            <ul class="list-unstyled">
                                ' . $promotionHtml . '
                            </ul>
                        </div>
                        <div class="advantages">
                            <p>' . $advPrice . '</p>
                        </div>
                        ' . $appendHtml . '
                    </div>';

        return $promoSec;
    }

    public function priceSection($priceHtml, $monthDurationPromo, $firstYearPrice, $cssClass = 'dealPrice', $appendHtml = '')
    {
        $priceSec = '<div class="' . $cssClass . '">
                        ' . $priceHtml . '
                        <div class="priceInfo">
                            <ul class="list-unstyled">
                                <li>' . $monthDurationPromo . '</li>
                                <li>' . $firstYearPrice . '
                                    <!--span class="calc">
                                        <a href="#"><i class="fa fa-calculator"></a></i>
                                    </span-->
                                </li>
                            </ul>
                        </div>
                        ' . html_entity_decode($appendHtml) . '
                     </div>';

        return $priceSec;
    }

    /**
     * @param array $prd
     * @return string
     */
    public function getTitleSection(array $prd)
    {
        $titleSec = '<h4>' . $prd['product_name'] . '</h4>
                     <p class="slogan">' . $prd['tagline'] . '</p>';
        return $titleSec;
    }

    public function getLogoSection(array $prd)
    {
        $logoSec = '<div class="dealLogo">
                        <img src="' . $prd['logo']['200x140']->color . '" alt="' . $prd['product_name'] . '">
                    </div>';
        return $logoSec;
    }

    public function getCustomerRatingSection($prd)
    {
        $custRatSec = '';
        if((float)$prd['score'] > 0) {
            $custRatSec = '<div class="customerRating">
                            <div class="stamp">
                                ' . $prd['score'] . '
                            </div>
                       </div>';
        }
        return $custRatSec;
    }

    /**
     * @param string $badgeTxt
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

    public function getProductDetailSection($prd, $servicesHtml, $includeBadge = false, $badgeTxt = '')
    {
        $detailsSec = '<div class="dealDetails">';

        if ($includeBadge && !empty($badgeTxt)) {
            $detailsSec = $this->getBadgeSection($badgeTxt);
        }

        $detailsSec .= $this->getCustomerRatingSection($prd) .
            $this->getLogoSection($prd) .
            $this->getTitleSection($prd) .
            $this->getServiceIconsSection($servicesHtml);

        $detailsSec .= '</div>';

        return $detailsSec;
    }

    /**
     * @param $prd
     * @return array
     */
    public function getPriceInfo(array $prd)
    {
        $advPrice = '&nbsp;';

        if (!empty($prd['advantage'])) {
            $advPrice = "-" . $prd['advantage'] . getCurrencySymbol($prd['currency_unit']) . ' ' . pll__('advantage');
        }

        $monthDurationPromo = '&nbsp;';
        //sprintf
        if (!empty($prd['monthly_promo_duration'])) {
            $monthDurationPromo = sprintf(pll__('the first %d months'), $prd['monthly_promo_duration']);
        }

        $firstYearPrice = '';
        if (intval($prd['year_1_promo']) > 0) {
            $firstYearPrice = getCurrencySymbol($prd['currency_unit']) . ' ' . intval($prd['year_1_promo']);
            $firstYearPrice = $firstYearPrice . ' ' . pll__('the first year');
        }
        return array($advPrice, $monthDurationPromo, $firstYearPrice);
    }
}
