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
            'detaillevel' => ['supplier', 'logo', 'services', 'price', 'reviews', 'texts'],//specifications, logo
            'sg' => 'consumer',
            'product_1' => [],
            'product_2' => [],
            'product_3' => [],
            'lang' => 'nl',
            'is_active' => 'no',
            'is_first' => 'no'

        ), $atts, 'anb_top_deal_products');

        if (!empty($atts['detaillevel'])) {
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
        $data = [];
        //for now hardcoded decimal separator to coma
        foreach($products as $idx => $product) {
            $data[$idx]['name'] = $product->product_name;
            $data[$idx]['tagline'] = isset($product->texts->tagline) ? $product->texts->tagline : "";
            $data[$idx]['price_detail'] = (array)$product->price;
            $data[$idx]['monthly_price'] = (array)$product->monthly_fee;
            //break price into chunks like price, cents and currency
            $monthlyPrice = $data[$idx]['monthly_price']['value'];
            $monthlyPriceArr = explode(".", $monthlyPrice);
			if(!isset($monthlyPriceArr[1])) {
				$monthlyPriceArr[1] = 0;
			}
            $data[$idx]['monthly_price_chunk'] = [
                'price' => $monthlyPriceArr[0],
                'cents' => ($monthlyPriceArr[1] < 10 ? '0'.$monthlyPriceArr[1] : "00"),
                'unit' => $data[$idx]['monthly_price']['unit']
            ];
            $data[$idx]['monthly_price_promo'] = isset($product->monthly_fee_promo) ? (array)$product->monthly_fee_promo : 0;

            if($data[$idx]['monthly_price_promo'] != 0) {
                //break price into chunks like price, cents and currency
                $monthlyPricePromo = $data[$idx]['monthly_price_promo']['value'];
                $monthlyPricePromoArr = explode(".", $monthlyPricePromo);

				if(!isset($monthlyPricePromoArr[1])) {
					$monthlyPricePromoArr[1] = 0;
				}
                $data[$idx]['monthly_price_promo_chunk'] = [
                    'price' => $monthlyPricePromoArr[0],
                    'cents' => ($monthlyPricePromoArr[1] < 10 ? '0'.$monthlyPricePromoArr[1] : "00"),
                    'unit' => $data[$idx]['monthly_price_promo']['unit']
                ];
            }

            $data[$idx]['services'] = (array)$product->supplier->services;
            $data[$idx]['logo'] = (array)$product->supplier->logo;
            $data[$idx]['review_score'] = str_replace(",", ".", $product->reviews->score);
            $promotions = (array)$product->promotions;
            foreach($promotions as $promotion) {
                $data[$idx]['promotions'][] = $promotion->texts->name;
            }
        }

        /*echo "<pre>";
        print_r($data);
        echo "</pre>";*/
        
        wp_enqueue_script('jquery');
        wp_enqueue_script( 'top_deals_js', plugin_dir_url(__FILE__ ) . 'js/top-deals.js' );

        $htmlWrapper = '';
        if($atts['is_first'] == 'yes') {
            $htmlWrapper = '<section class="topDeals">
                        <div class="container">
                            <div class="topDealsWrapper">
                                <h3>'.pll__('Proximus most popular').'</h3>
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
        if($atts['is_active'] == 'yes') {
            $class = 'class="active"';
        } else {
            $displayStyle = 'style="display:none;"';
        }

        $navHtmlName = sanitize_title_with_dashes($nav);
        $navContent = '<div class="row '.$navHtmlName.'" '.$displayStyle.'>';
        foreach($data as $idx => $prd) {
            $boxClass = 'left';
            if($idx == 1) {
                $boxClass = 'center';
            } elseif($idx == 2) {
                $boxClass = 'right';
            }

            //Services HTML
            $servicesHtml = '';
            if(in_array("internet", $prd['services'])) {
                $servicesHtml .= '<li>
                                    <i class="service-icons wifi"></i>
                                  </li>';
            }
            if(in_array("mobile", $prd['services'])) {
                $servicesHtml .= '<li>
                                    <i class="service-icons mobile"></i>
                                  </li>';
            }
            if(in_array("telephony", $prd['services'])) {
                $servicesHtml .= '<li>
                                    <i class="service-icons phone"></i>
                                  </li>';
            }
            if(in_array("idtv", $prd['services'])) {
                $servicesHtml .= '<li>
                                    <i class="service-icons tv"></i>
                                  </li>';
            }

            //Price HTML
            /**
            <div class="oldPrice">
            <span class="amount">110</span><span class="cents">95</span>
            </div>
            <div class="newPrice">
            <span class="amount">97<span class="cents">95</span><span class="recursion">/mth</span></span>
            </div>
             */
            $priceHtml = '';
            if(isset($prd['monthly_price_promo_chunk'])) {
                $priceHtml .= '<div class="oldPrice">
                                <span class="amount">'.$prd['monthly_price_chunk']['price'].'</span>';
                if(isset($prd['monthly_price_chunk']['cents']) && !empty($prd['monthly_price_chunk']['cents'])) {
                    $priceHtml .= '<span class="cents">'.$prd['monthly_price_chunk']['cents'].'</span>';
                }
                $priceHtml .= '</div>';

                $priceHtml .= '<div class="newPrice">
                                <span class="amount">'.$prd['monthly_price_chunk']['price'];
                if(isset($prd['monthly_price_chunk']['cents']) && !empty($prd['monthly_price_chunk']['cents'])) {
                    $priceHtml .= '<span class="cents">'.$prd['monthly_price_chunk']['cents'].'</span>';
                }
                $priceHtml .= '<span class="recursion">/mth</span></span>
                               </div>';
            } else {
                $priceHtml .= '<div class="newPrice">
                                <span class="amount">'.$prd['monthly_price_chunk']['price'];
                if(isset($prd['monthly_price_chunk']['cents']) && !empty($prd['monthly_price_chunk']['cents'])) {
                    $priceHtml .= '<span class="cents">'.$prd['monthly_price_chunk']['cents'].'</span>';
                }
                $priceHtml .= '<span class="recursion">/mth</span></span>
                               </div>';
            }

            //Promotions, Installation/Activation HTML
            $promotionHtml = '';
            //print_r($prd[$idx]['promotions']);
            if(!isset($prd['promotions'])) {
                //in case of no promotions, display installation and activation price
                if($prd['price_detail']['installation_full'] >= 0.5) {
                    $promotionHtml .= '<li>'.pll__('Installation').' '.$prd['price_detail']['installation_full'].'</li>';
                }
                if($prd['price_detail']['activation'] >= 0.5) {
                    $promotionHtml .= '<li>'.pll__('Activation').' '.$prd['price_detail']['activation'].'</li>';
                }
            } else {
                //display only promotions
                foreach($prd['promotions'] as $promotion) {
                    $promotionHtml .= '<li class="prominent">'.$promotion.'</li>';
                }
            }

            $navContent .= '<div class="col-md-4 offer '.$boxClass.'">
                                <div class="dealDetails">
                                    <!--div class="bestReviewBadge">
                                        <span>BEST</span>
                                        <span class="bold">Review</span>
                                    </div-->
                                    <div class="customerRating">
                                        <div class="stamp">
                                            '.$prd['review_score'].'
                                        </div>
                                    </div>
                                    <div class="dealLogo">
                                        <img src="'.$prd['logo']['200x140']->color.'" alt="'.$prd['name'].'">
                                    </div>
                                    <h4>'.$prd['name'].'</h4>
                                    <p class="slogan">'.$prd['tagline'].'</p>
                                    <div class="services">
                                        <ul class="list-unstyled list-inline">
                                            '.$servicesHtml.'
                                        </ul>
                                    </div>
                                </div>
                                <div class="dealPrice">
                                    '.$priceHtml.'
                                    <!--div class="priceInfo">
                                        <ul class="list-unstyled">
                                            <li>the first 6 months</li>
                                            <li>€ 1200 the first year
                                                <span class="calc">
                                                    <a href="#"><i class="fa fa-calculator"></a></i>
                                                </span>
                                            </li>
                                        </ul>
                                    </div-->
                                </div>
                                <div class="dealFeatures">
                                    <div class="extras">
                                        <ul class="list-unstyled">
                                            '.$promotionHtml.'
                                        </ul>
                                    </div>
                                    <!--div class="advantages">
                                        <p>&nbsp;</p>
                                    </div-->
                                </div>
                                <a href="#" class="btn btn-primary">Info and options</a>
                            </div>';
        }
        $navContent .= '</div>';

        $navHtml = '<li '.$class.'><a href="javascript:void(0);" related="'.$navHtmlName.'">'.pll__($nav).'</a></li>';

        //$script = '<script>appendToSelector(".topDeals .filterDeals ul", {"html": \''.$navHtml.'\'}); appendToSelector(".topDeals .dealsTable", {"html": \''.$navContent.'\'})</script>';
        $script = '<script>
                    $(document).ready(function(){
                        appendToSelector(".topDeals .filterDeals ul",  \''.$navHtml.'\'); 
                        appendToSelector(".topDeals .dealsTable", \''.$this->minifyHtml($navContent).'\')
                    });
                   </script>';
        echo $script;

        //return "<pre>" . print_r($params, true) . "<br><br>" . print_r($products, true) . "</pre>";
    }

    function minifyHtml($buffer) {

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
}
