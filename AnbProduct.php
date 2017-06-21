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
            'detaillevel' => [],//specifications, logo
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

        /*echo "<pre>PRODUCTS>>>";
        print_r($products);
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

        $navContent = '<div class="row '.$navHtmlName.'" '.$displayStyle.'>
                        <div class="col-md-4 offer left">
                            <div class="dealDetails">
                                <div class="bestReviewBadge">
                                    <span>BEST</span>
                                    <span class="bold">Review</span>
                                </div>
                                <div class="customerRating">
                                    <div class="stamp">
                                        8.4
                                    </div>
                                </div>
                                <div class="dealLogo">
                                    <img src="'.get_template_directory_uri().'/images/common/providers/proximus.png" alt="Proximus Tuttimus">
                                </div>
                                <h4>Proximus Tuttimus ('.$nav.')</h4>
                                <p class="slogan">De eerste all-in voor je gezin</p>
                                <div class="services">
                                    <ul class="list-unstyled list-inline">
                                        <li>
                                            <i class="fa fa-wifi"></i>
                                        </li>
                                        <li>
                                            <i class="fa fa-mobile"></i>
                                        </li>
                                        <li>
                                            <i class="fa fa-phone"></i>
                                        </li>
                                        <li>
                                            <i class="fa fa-tv"></i>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="dealPrice">
                                <div class="oldPrice">
                                    <span class="amount">110</span><span class="cents">95</span>
                                </div>
                                <div class="newPrice">
                                    <span class="amount">97<span class="cents">95</span><span class="recursion">/mth</span></span>
                                </div>
                                <div class="priceInfo">
                                    <ul class="list-unstyled">
                                        <li>the first 6 months</li>
                                        <li>€ 1200 the first year
                                            <span class="calc">
                                                <a href="#"><i class="fa fa-calculator"></a></i>
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="dealFeatures">
                                <div class="extras">
                                    <ul class="list-unstyled">
                                        <li>Installation 65€</li>
                                        <li>Activation 50€</li>
                                    </ul>
                                </div>
                                <div class="advantages">
                                    <p>&nbsp;</p>
                                </div>
                            </div>
                            <a href="#" class="btn btn-primary">Info and options</a>
                        </div>
                        <div class="col-md-4 offer center">
                            <div class="dealDetails">
                                <div class="bestReviewBadge">
                                    <span>BEST</span>
                                    <span class="bold">Promo</span>
                                </div>
                                <div class="customerRating">
                                    <div class="stamp">
                                        8.4
                                    </div>
                                </div>
                                <div class="dealLogo">
                                    <img src="'.get_template_directory_uri().'/images/common/providers/telenet.png" alt="Proximus Tuttimus">
                                </div>
                                <h4>Proximus Tuttimus</h4>
                                <p class="slogan">De eerste all-in voor je gezin</p>
                                <div class="services">
                                    <ul class="list-unstyled list-inline">
                                        <li>
                                            <i class="fa fa-wifi"></i>
                                        </li>
                                        <li>
                                            <i class="fa fa-mobile"></i>
                                        </li>
                                        <li>
                                            <i class="fa fa-phone"></i>
                                        </li>
                                        <li>
                                            <i class="fa fa-tv"></i>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="dealPrice">
                                <div class="oldPrice">
                                    <span class="amount">99</span><span class="cents">95</span>
                                </div>
                                <div class="newPrice">
                                    <span class="amount">97<span class="cents">95</span><span class="recursion">/mth</span></span>
                                </div>
                                <div class="priceInfo">
                                    <ul class="list-unstyled">
                                        <li>the first 6 months</li>
                                        <li>€ 1200 the first year
                                            <span class="calc">
                                                <a href="#"><i class="fa fa-calculator"></a></i>
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="dealFeatures">
                                <div class="extras">
                                    <ul class="list-unstyled">
                                        <li>Installation 65€</li>
                                        <li class="prominent">Free activation t.w.v. 50€</li>
                                    </ul>
                                </div>
                                <div class="advantages">
                                    <p>-50€ advantage</p>
                                </div>
                            </div>
                            <a href="#" class="btn btn-primary">Info and options</a>
                        </div>
                        <div class="col-md-4 offer right">
                            <div class="dealDetails">
                                <div class="customerRating">
                                    <div class="stamp">
                                        8.4
                                    </div>
                                </div>
                                <div class="dealLogo">
                                    <img src="'.get_template_directory_uri().'/images/common/providers/telenet.png" alt="Proximus Tuttimus">
                                </div>
                                <h4>Proximus Tuttimus</h4>
                                <p class="slogan">De eerste all-in voor je gezin</p>
                                <div class="services">
                                    <ul class="list-unstyled list-inline">
                                        <li>
                                            <i class="fa fa-wifi"></i>
                                        </li>
                                        <li>
                                            <i class="fa fa-mobile"></i>
                                        </li>
                                        <li>
                                            <i class="fa fa-phone"></i>
                                        </li>
                                        <li>
                                            <i class="fa fa-tv"></i>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="dealPrice">
                                <div class="oldPrice">
                                    <span class="amount">110</span><span class="cents">95</span>
                                </div>
                                <div class="newPrice">
                                    <span class="amount">97<span class="cents">95</span><span class="recursion">/mth</span></span>
                                </div>
                                <div class="priceInfo">
                                    <ul class="list-unstyled">
                                        <li>the first 6 months</li>
                                        <li>€ 1200 the first year
                                            <span class="calc">
                                                <a href="#"><i class="fa fa-calculator"></a></i>
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="dealFeatures">
                                <div class="extras">
                                    <ul class="list-unstyled">
                                        <li>Installation 65€</li>
                                        <li class="prominent">Free activation t.w.v. 50€</li>
                                        <li class="prominent">10€ discount</li>
                                    </ul>
                                </div>
                                <div class="advantages">
                                    <p>-50€ advantage</p>
                                </div>
                            </div>
                            <a href="#" class="btn btn-primary">Info and options</a>
                        </div>
                    </div>';

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
