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

    function topDealProducts($atts, $nav="")
    {
        $atts = shortcode_atts(array(
            'cat' => '',
            'detaillevel' => [],//specifications, logo
            'sg' => 'consumer',
            'product_1' => [],
            'product_2' => [],
            'product_3' => [],
            'lang' => 'nl',
            'is_active' => 0

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

        $class = '';
        /*if($atts['is_active'] == 1) {
            $class = "<div class='{$class}'>";
        }*/
        //Write here jQuery code to manipulate the navigation, because there is a chance that some content is already rendered

        return "<pre>" . print_r($params, true) . "<br><br>" . print_r($products, true) . "</pre>";
    }
}
