<?php
/**
 * Created by PhpStorm.
 * User: imran
 * Date: 5/12/17
 * Time: 6:02 PM
 */

namespace AnbTopDeals;

use AnbApiClient\Aanbieders;

class AnbProduct {

	public $crmApiEndpoint = "http://api.econtract.be/";//Better to take it from Admin settings
	/** @var $anbApi \AnbApiClient\Aanbieders */
	public $anbApi;
	public $apiConf = [
		'staging' => ANB_API_STAGING,
		'key'     => ANB_API_KEY,
		'secret'  => ANB_API_SECRET
	];

	public function __construct() {
		$this->anbApi = wpal_create_instance( Aanbieders::class, [ $this->apiConf ] );
	}

	function topDealProducts( $atts, $nav = "" ) {
		$atts = shortcode_atts( array(
			'cat'         => '',
			'detaillevel' => [ 'supplier', 'logo', 'services', 'price', 'reviews', 'texts', 'promotions' ],
			//specifications, logo
			'sg'          => 'consumer',
			'product_1'   => [],
			'product_2'   => [],
			'product_3'   => [],
			'lang'        => 'nl',
			'is_active'   => 'no',
			'is_first'    => 'no'

		), $atts, 'anb_top_deal_products' );

		if ( ! empty( $atts['detaillevel'] ) && is_string( $atts['detaillevel'] ) ) {
			$atts['detaillevel'] = explode( ',', $atts['detaillevel'] );
		}

		if ( empty( $atts['product_1'] ) || empty( $atts['product_2'] ) || empty( $atts['product_3'] ) || empty( $nav ) ) {
			return;
		}

		$nav = sanitize_text_field( $nav );

		pll_register_string( $nav, $nav, 'AnbTopDeals' );

		//remove empty params
		$params = array_filter( $atts );

		// get the products
		//$products = $this->anbApi->getProducts($params, $atts['product_ids']);

		/*$products = $this->anbApi->getProducts(array('cat'=>array('dualfuel_pack', 'internet'), 'ssg'=>'consumer', 'lang'=>'nl', 'status'=>array(0,1,2),
			'productid'=>array('internet|180','dualfuel_pack|11', 'dualfuel_pack|18'),
			'detaillevel'=>array('ddspecifications'), 'a'=>31));*/

		//Extract categories from each product
		$cats   = [];
		$cats[] = substr( $atts['product_1'], 0, strpos( $atts['product_1'], "|" ) );
		$cats[] = substr( $atts['product_2'], 0, strpos( $atts['product_2'], "|" ) );
		$cats[] = substr( $atts['product_3'], 0, strpos( $atts['product_3'], "|" ) );

		$cats = array_unique( $cats );

		$cacheTime = 86400;

		if(defined('TOP_DEALS_PRODUCT_CACHE_DURATION')) {
            $cacheTime = TOP_DEALS_PRODUCT_CACHE_DURATION;
        }

        $products = $this->getProducts( array(
            'cat'         => $cats,
            'sg'          => $atts['sg'],
            'lang'        => $atts['lang'],
            'productid'   => array( $atts['product_1'], $atts['product_2'], $atts['product_3'] ),
            'detaillevel' => $atts['detaillevel']
        ), null, false, 0 );//don't cache top deals

		$products = json_decode( $products );

		/*echo "<pre>PRODUCTS>>>";
		print_r($products);
		echo "</pre>";*/

		//prepare product data to be displayed
		$data = $this->prepareProductsData( $products );

		/*echo "<pre>DATA>>>";
		print_r($data);
		echo "</pre>";*/

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'top_deals_js', plugin_dir_url( __FILE__ ) . 'js/top-deals.js' );

		$htmlWrapper = '';
		if ( $atts['is_first'] == 'yes' ) {
			$htmlWrapper = '<section class="topDeals">
                        <div class="container">
                            <div class="topDealsWrapper">
                                <h3>' . pll__( 'Proximus most popular' ) . '</h3>
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
		$class        = '';
		$displayStyle = '';
		if ( $atts['is_active'] == 'yes' ) {
			$class = 'class="active"';
		} else {
			$displayStyle = 'style="display:none;"';
		}

		$navHtmlName = sanitize_title_with_dashes( $nav );
		$navContent  = '<div class="row ' . $navHtmlName . '" ' . $displayStyle . '>';
		foreach ( $data as $idx => $prd ) {
			$boxClass = 'left';
			if ( $idx == 1 ) {
				$boxClass = 'center';
			} elseif ( $idx == 2 ) {
				$boxClass = 'right';
			}

			//Services HTML
			$servicesHtml = $this->getServicesHtml( $prd );

			//Price HTML
			$priceHtml = $this->getPriceHtml( $prd );

			//Promotions, Installation/Activation HTML
			//display installation and activation price
			$promotionHtml = $this->getPromoInternalSection( $prd );


			list( $advPrice, $monthDurationPromo, $firstYearPrice ) = $this->getPriceInfo( $prd );

			$navContent .= '<div class="col-md-4 offer ' . $boxClass . '">
                                ' . $this->getProductDetailSection( $prd, $servicesHtml ) .
			               $this->priceSection( $priceHtml, $monthDurationPromo, $firstYearPrice ) .
			               $this->getPromoSection( $promotionHtml, $advPrice ) . '
                                <a href="/' . pll__( 'brands' ) . '/' . $prd['supplier_slug'] . '/' . $prd['product_slug'] . '" class="btn btn-primary">' . pll__( 'Info and options' ) . '</a>
                            </div>';
		}
		$navContent .= '</div>';

		$navHtml = '<li ' . $class . '><a href="javascript:void(0);" related="' . $navHtmlName . '">' . pll__( $nav ) . '</a></li>';

		//$script = '<script>appendToSelector(".topDeals .filterDeals ul", {"html": \''.$navHtml.'\'}); appendToSelector(".topDeals .dealsTable", {"html": \''.$navContent.'\'})</script>';
		$script = '<script>
                    jQuery(document).ready(function($){
                        appendToSelector(".topDeals .filterDeals ul",  \'' . $navHtml . '\'); 
                        appendToSelector(".topDeals .dealsTable", \'' . $this->minifyHtml( $navContent ) . '\')
                    });
                   </script>';
		echo $script;

		//return "<pre>" . print_r($params, true) . "<br><br>" . print_r($products, true) . "</pre>";
	}

	function minifyHtml( $buffer ) {

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

		$buffer = preg_replace( $search, $replace, $buffer );

		return $buffer;
	}

	function getActivationOrInstPriceHtml( $priceDetailArray, $key, $currencySymbol = '', $onlyArray = false, $withFirstTerm = true ) {
		//translations in function: pll__('Free installation'), pll__('Free activation'), pll__('Installation'), pll__('t.w.v')
		$html         = '';
		$firstTerm    = explode( '_', $key )[0];//first term before underscore like installation from installation_full
		$firstTermLbl = ucfirst( $firstTerm );

		if ( $onlyArray ) {
			$prices                     = [];
			$prices[ $key . '_actual' ] = $priceDetailArray[ $key ];
			if ( $priceDetailArray[ $key ] > 0 ) {
				if ( $priceDetailArray[ $key . '_promo' ] > 0
				     && $priceDetailArray[ $key . '_promo' ] != $priceDetailArray[ $key ]
				) {//there is a promotional price as well

					$prices[ $key ]            = $priceDetailArray[ $key ];
					$prices[ $key . '_promo' ] = $priceDetailArray[ $key . '_promo' ];

				} elseif ( $priceDetailArray[ $key . '_promo' ] == 0 ) {
					if ( $withFirstTerm ) {
						$prices[ $key ] = pll__( 'Free' );
					} else {
						$prices[ $key ] = pll__( 'Free' . ( ! empty( $firstTerm ) ) ? ' ' . $firstTerm : '' );
					}
					$prices[ $key . '_free' ] = true;
				} else {
					$prices[ $key ] = round( $priceDetailArray[ $key ] );
				}
			} else {
				if ( $withFirstTerm ) {
					$prices[ $key ] = pll__( 'Free' );
				} else {
					$prices[ $key ] = pll__( 'Free' . ( ! empty( $firstTerm ) ) ? ' ' . $firstTerm : '' );
				}
				$prices[ $key . '_free' ] = true;
			}

			return $prices;
		}

		//display installation and activation price
		if ( $priceDetailArray[ $key ] > 0 ) {
			if ( $priceDetailArray[ $key . '_promo' ] > 0
			     && $priceDetailArray[ $key . '_promo' ] != $priceDetailArray[ $key ]
			) {//there is a promotional price as well
				$html .= '<li class="prominent">' . pll__( $firstTermLbl ) . ' ' . $priceDetailArray[ $key . '_promo' ] .
				         $currencySymbol . ' ' . pll__( 't.w.v' ) . ' ' . $priceDetailArray[ $key ] .
				         $currencySymbol . '</li>';
			} elseif ( $priceDetailArray[ $key . '_promo' ] == 0 ) {
				$html .= '<li class="prominent">' . pll__( 'Free ' . $firstTerm ) . ' ' . pll__( 't.w.v' ) .
				         ' ' . $priceDetailArray[ $key ] . $currencySymbol . '</li>';
			} else {
				$html .= '<li>' . pll__( $firstTermLbl ) . ' ' . round( $priceDetailArray[ $key ] ) .
				         $currencySymbol . '</li>';
			}
		} else {
			$html .= '<li class="prominent">' . pll__( 'Free ' . $firstTerm ) . '</li>';
		}

		return $html;
	}

	function getActOrInstPriceBreakDownHtml( $priceArray, $key, $currencySymbol = '' ) {
		if ( empty( $priceArray[ $key ] ) ) {
			return '';
		}

		$firstTerm = explode( '_', $key )[0];//first term before underscore like installation from installation_full

		$promoPriceHtml = ( ! empty( $priceArray[ $key . '_promo' ] ) && $priceArray[ $key . '_promo' ] != $priceArray[ $key . '_actual' ] ) ? '<span class="saving-price">' . $currencySymbol . ' ' . $priceArray[ $key . '_actual' ] . '</span>' : '';
		$html           = '<li>' . pll__( ucfirst( $firstTerm ) . ' cost' ) . '
					' . $promoPriceHtml . '
                    <span class="cost-price">' . $currencySymbol . ' ' . $priceArray[ $key ] . '</span>
                 </li>';

		if ( $priceArray[ $key . '_free' ] === true ) {
			$html = '<li class="prominent">' . pll__( ucfirst( $firstTerm ) . ' cost' ) . '<span class="cost-price">' . pll__( 'Free' ) . '</span></li>';
		}

		return $html;
	}

	/**
	 * @param array $prd
	 *
	 * @return string
	 */
	function getServicesHtml( array $prd ) {
		$servicesHtml = '';

		$prdOrPckTypes = ( $prd['producttype'] == 'packs' ) ? $prd['packtype'] : $prd['producttype'];
		$prdOrPckTypes = strtolower( $prdOrPckTypes );

		if ( strpos( $prdOrPckTypes, "int" ) !== false ) {
			$servicesHtml .= '<li>
                                <i class="service-icons wifi"></i>
                              </li>';
		}
		if ( strpos( $prdOrPckTypes, "gsm" ) !== false ) {
			$servicesHtml .= '<li>
                                <i class="service-icons mobile"></i>
                              </li>';
		}
		if ( strpos( $prdOrPckTypes, "tel" ) !== false ) {
			$servicesHtml .= '<li>
                                <i class="service-icons phone"></i>
                              </li>';
		}
		if ( strpos( $prdOrPckTypes, "tv" ) !== false ) {
			$servicesHtml .= '<li>
                                <i class="service-icons tv"></i>
                              </li>';
		}

		return $servicesHtml;
	}

	/**
	 * @param array $products
	 *
	 * @return array
	 */
	public function prepareProductsData( array $products ) {
		$data = [];
		//for now hardcoded decimal separator to coma
		foreach ( $products as $idx => $product ) {
			$data[ $idx ] = $this->prepareProductData( $product );
		}

		return $data;
	}

	/**
	 * @param object $product
	 *
	 * @return array
	 */
	public function prepareProductData( $product ) {
		$data = [];
		//for now hardcoded decimal separator to coma
		//Pack type: 'int_tel', 'tv_tel', 'int_tel_tv', 'gsm_int_tel_tv', 'int_tv', 'gsm_int_tv'
		$data['producttype'] = $product->producttype;

		if ( $product->producttype == "packs" ) {
			$data['packtype'] = $product->packtype;
		}

		$data['product_name']  = $product->product_name;
		$data['product_slug']  = $product->product_slug;
		$data['supplier_id']   = $product->supplier_id;
		$data['supplier_slug'] = $product->supplier_slug;
		$data['supplier_name'] = $product->supplier_name;
		$data['product_id']    = $product->product_id;
		$data['tagline']       = isset( $product->texts->tagline ) ? $product->texts->tagline : "";
		$data['price']         = (array) $product->price;
		$data['monthly_fee']   = (array) $product->monthly_fee;
		$data['advantage']     = $product->price->advantage;
		$data['currency_unit'] = $data['monthly_fee']['unit'];
		$data['year_1_promo']  = $product->price->year_1_promo;
		$data['supplier_is_partner'] = $product->supplier->is_partner;
		//break price into chunks like price, cents and currency
		$monthlyPrice    = $data['monthly_fee']['value'];
		$monthlyPriceArr = explode( ".", $monthlyPrice );
		if ( ! isset( $monthlyPriceArr[1] ) ) {
			$monthlyPriceArr[1] = 0;
		}
		$data['monthly_price_chunk'] = [
			'price' => $monthlyPriceArr[0],
			'cents' => ( $monthlyPriceArr[1] < 10 ? '0' . $monthlyPriceArr[1] : $monthlyPriceArr[1] ),
			'unit'  => $data['monthly_fee']['unit']
		];
//            echo "+++".print_r($product->price, true)."<br>";
		$data['monthly_promo']          = isset( $product->price->monthly_promo ) ? $product->price->monthly_promo : 0;
		$data['monthly_promo_duration'] = isset( $product->price->monthly_promo_duration ) ? $product->price->monthly_promo_duration : 0;

		//in case normal price and promo price are not same
		if ( $product->price->monthly_promo != $product->price->monthly ) {
			//break price into chunks like price, cents and currency
			$monthlyPricePromo    = $data['monthly_promo'];
			$monthlyPricePromoArr = explode( ".", $monthlyPricePromo );

			if ( ! isset( $monthlyPricePromoArr[1] ) ) {
				$monthlyPricePromoArr[1] = 0;
			}
			$data['monthly_promo_price_chunk'] = [
				'price'    => $monthlyPricePromoArr[0],
				'cents'    => ( $monthlyPricePromoArr[1] < 10 ? '0' . $monthlyPricePromoArr[1] : $monthlyPriceArr[1] ),
				'unit'     => $data['monthly_price_chunk']['unit'],//use unit of normal monthly price
				'duration' => $data['monthly_promo_duration']
			];
			//echo "+++".print_r($data['monthly_promo_price_chunk'], true)."<br>";
		}

		$data['services'] = (array) $product->supplier->services;
		$data['logo']     = (array) $product->supplier->logo;
		$data['score']    = convertToSiteScore( $product->reviews->score );
		$promotions       = (array) $product->promotions;
		foreach ( $promotions as $promotion ) {
			$data['promotions'][] = $promotion->texts->name;
		}

		return $data;
	}

	/**
	 * @param array $prd
	 *
	 * @return string
	 */
	public function getPriceHtml( array $prd ) {
		$priceHtml = '';

		if ( $prd['monthly_price_chunk']['cents'] == "000" ) {
			$prd['monthly_price_chunk']['cents'] = "00";
		}

		if ( isset( $prd['monthly_promo_price_chunk'] ) ) {
			$priceHtml .= '<div class="oldPrice">
                                <span class="amount">' . $prd['monthly_price_chunk']['price'] . '</span>';
			if ( isset( $prd['monthly_price_chunk']['cents'] ) && ! empty( $prd['monthly_price_chunk']['cents'] ) ) {
				$priceHtml .= '<span class="cents">' . $prd['monthly_price_chunk']['cents'] . '</span>';
			}
			$priceHtml .= '</div>';

			$priceHtml .= '<div class="newPrice">
                                <span class="amount">' . $prd['monthly_promo_price_chunk']['price'];
			if ( isset( $prd['monthly_price_chunk']['cents'] ) && ! empty( $prd['monthly_price_chunk']['cents'] ) ) {
				$priceHtml .= '<span class="cents">' . $prd['monthly_promo_price_chunk']['cents'] . '</span>';
			}
			$priceHtml .= '<span class="recursion">/mth</span></span>
                               </div>';
		} else {
            $priceHtml .= '<div class="oldPrice"></div>';
			$priceHtml .= '<div class="newPrice">
                                <span class="amount">' . $prd['monthly_price_chunk']['price'];
			if ( isset( $prd['monthly_price_chunk']['cents'] ) && ! empty( $prd['monthly_price_chunk']['cents'] ) ) {
				$priceHtml .= '<span class="cents">' . $prd['monthly_price_chunk']['cents'] . '</span>';
			}
			$priceHtml .= '<span class="recursion">/mth</span></span>
                               </div>';
		}

		return $priceHtml;
	}

	/**
	 * @param array $prd
	 *
	 * @return string
	 */
	public function getPromoHtml( array $prd ) {
		//display promotions
		$promotionHtml = '';
		foreach ( $prd['promotions'] as $promotion ) {
			$promotionHtml .= '<li class="prominent">' . $promotion . '</li>';
		}

		return $promotionHtml;
	}

	/**
	 * @param array $prd
	 * @param boolean $withoutPromoList
	 *
	 * @return string
	 */
	public function getPromoInternalSection( array $prd, $withoutPromoList = false ) {
		$promotionHtml = '';
		$promotionHtml .= $this->getActivationOrInstPriceHtml( $prd['price'], 'installation_full', getCurrencySymbol( $prd['currency_unit'] ) );
		$promotionHtml .= $this->getActivationOrInstPriceHtml( $prd['price'], 'activation', getCurrencySymbol( $prd['currency_unit'] ) );
		if ( ! $withoutPromoList ) {
			$promotionHtml .= $this->getPromoHtml( $prd );
		}

		return $promotionHtml;
	}

	public function getServiceIconsSection( $servicesHtml ) {
		$serviceSec = '<div class="services">
                            <ul class="list-unstyled list-inline">
                                ' . $servicesHtml . '
                            </ul>
                         </div>';

		return $serviceSec;
	}

	public function getPromoSection( $promotionHtml, $advPrice, $cssClass = 'dealFeatures', $appendHtml = '' ) {
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

	public function priceSection( $priceHtml, $monthDurationPromo, $firstYearPrice, $cssClass = 'dealPrice', $appendHtml = '', $calcHtml = false, $productData = '' ) {
		if ( $calcHtml ) {
			$calcHtml = '<span class="calc">
                            <a href="#" data-toggle="modal" data-target="#calcBreakdown' . $productData['product_id'] . '">
                                    <i class="custom-icons calc"></i>
                            </a>
                         </span>';
			$this->calculatorPopup( $productData );
		}

		$priceSec = '<div class="' . $cssClass . '">
                        ' . $priceHtml . '
                        <div class="priceInfo">
                            <ul class="list-unstyled">
                                <li>' . $monthDurationPromo . '</li>
                                <li>' . $firstYearPrice
		            . $calcHtml .
		            '</li>
                            </ul>
                        </div>
                        ' . html_entity_decode( $appendHtml ) . '
                     </div>';

		return $priceSec;
	}

	/**
	 * @param $productData
	 */
	public function calculatorPopup( $productData ) {
		$html               = "<div class='modal borderLess fade' id='calcBreakdown{$productData['product_id']}'  tabindex='-1' role='dialog' aria-labelledby='calcBreakdownLabel'>";
		$priceBreakDownHtml = $this->getProductPriceBreakdownHtml( $productData );
		$html               .= '<div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title text-left">
                        <svg class="calculator" height="26px" viewBox="0 0 291 393" fill="#33515C">
                            <path d="M232.806181,0 L58.193819,0 C26.1918543,0 0,26.2144262 0,58.2096279 L0,334.790372 C0,366.80103 26.1918543,393 58.193819,393 L232.806181,393 C264.808146,393 291,366.80103 291,334.790372 L291,58.2096279 C291,26.2144262 264.808146,0 232.806181,0 Z M93.5644116,334.790372 C93.5644116,342.765988 86.9816801,349.350507 78.946421,349.350507 L58.193819,349.350507 C50.1585599,349.350507 43.6376381,342.765988 43.6376381,334.790372 L43.6376381,313.970306 C43.6376381,305.99469 50.1585599,299.410171 58.193819,299.410171 L78.946421,299.410171 C86.9816801,299.410171 93.5644116,305.99469 93.5644116,313.970306 L93.5644116,334.790372 Z M93.5644116,257.816408 C93.5644116,265.838394 86.9816801,272.361087 78.946421,272.361087 L58.193819,272.361087 C50.1585599,272.361087 43.6376381,265.838394 43.6376381,257.816408 L43.6376381,237.042712 C43.6376381,229.082553 50.1585599,222.498034 58.193819,222.498034 L78.946421,222.498034 C86.9816801,222.498034 93.5644116,229.082553 93.5644116,237.042712 L93.5644116,257.816408 Z M93.5644116,180.888815 C93.5644116,188.926257 86.9816801,195.44895 78.946421,195.44895 L58.193819,195.44895 C50.1585599,195.44895 43.6376381,188.926257 43.6376381,180.888815 L43.6376381,160.130575 C43.6376381,152.093133 50.1585599,145.508613 58.193819,145.508613 L78.946421,145.508613 C86.9816801,145.508613 93.5644116,152.093133 93.5644116,160.130575 L93.5644116,180.888815 Z M170.455661,334.790372 C170.455661,342.765988 163.872929,349.350507 155.914932,349.350507 L135.085068,349.350507 C127.127071,349.350507 120.544339,342.765988 120.544339,334.790372 L120.544339,313.970306 C120.544339,305.99469 127.127071,299.410171 135.085068,299.410171 L155.914932,299.410171 C163.872929,299.410171 170.455661,305.99469 170.455661,313.970306 L170.455661,334.790372 Z M170.455661,257.816408 C170.455661,265.838394 163.872929,272.361087 155.914932,272.361087 L135.085068,272.361087 C127.127071,272.361087 120.544339,265.838394 120.544339,257.816408 L120.544339,237.042712 C120.544339,229.082553 127.127071,222.498034 135.085068,222.498034 L155.914932,222.498034 C163.872929,222.498034 170.455661,229.082553 170.455661,237.042712 L170.455661,257.816408 Z M170.455661,180.888815 C170.455661,188.926257 163.872929,195.44895 155.914932,195.44895 L135.085068,195.44895 C127.127071,195.44895 120.544339,188.926257 120.544339,180.888815 L120.544339,160.130575 C120.544339,152.093133 127.127071,145.508613 135.085068,145.508613 L155.914932,145.508613 C163.872929,145.508613 170.455661,152.093133 170.455661,160.130575 L170.455661,180.888815 Z M247.362362,334.790372 C247.362362,342.765988 240.77963,349.350507 232.806181,349.350507 L211.991769,349.350507 C204.01832,349.350507 197.435588,342.765988 197.435588,334.790372 L197.435588,313.970306 C197.435588,305.99469 204.01832,299.410171 211.991769,299.410171 L232.806181,299.410171 C240.77963,299.410171 247.362362,305.99469 247.362362,313.970306 L247.362362,334.790372 Z M247.362362,257.816408 C247.362362,265.838394 240.77963,272.361087 232.806181,272.361087 L211.991769,272.361087 C204.01832,272.361087 197.435588,265.838394 197.435588,257.816408 L197.435588,237.042712 C197.435588,229.082553 204.01832,222.498034 211.991769,222.498034 L232.806181,222.498034 C240.77963,222.498034 247.362362,229.082553 247.362362,237.042712 L247.362362,257.816408 Z M247.362362,180.888815 C247.362362,188.926257 240.77963,195.44895 232.806181,195.44895 L211.991769,195.44895 C204.01832,195.44895 197.435588,188.926257 197.435588,180.888815 L197.435588,160.130575 C197.435588,152.093133 204.01832,145.508613 211.991769,145.508613 L232.806181,145.508613 C240.77963,145.508613 247.362362,152.093133 247.362362,160.130575 L247.362362,180.888815 Z M247.362362,101.920947 C247.362362,109.896563 240.77963,116.465626 232.806181,116.465626 L58.193819,116.465626 C50.2203696,116.465626 43.6376381,109.896563 43.6376381,101.920947 L43.6376381,66.5407457 C43.6376381,58.5651302 50.2203696,51.9806104 58.193819,51.9806104 L232.806181,51.9806104 C240.84144,51.9806104 247.362362,58.5033037 247.362362,66.5407457 L247.362362,101.920947 Z"
                                  id="Fill-1"></path>
                            <path d="M151.187305,64 C140.932362,64 136,73.0626662 136,84.5545006 C136.062435,95.7504747 140.635796,105 150.87513,105 C161.052029,105 166,96.5446259 166,84.3209267 C166,73.4208128 161.848075,64 151.187305,64 Z M151.12487,97.9460691 C147.519251,97.9460691 145.334027,93.6171667 145.396462,84.5545006 C145.334027,75.3205469 147.644121,70.9916445 151.062435,70.9916445 C154.777315,70.9916445 156.728408,75.6164071 156.728408,84.4454994 C156.665973,93.4458792 154.71488,97.9460691 151.12487,97.9460691 Z"
                                  id="Fill-2"></path>
                            <path d="M185.171696,64 C174.869927,64 170,73.0626662 170,84.5545006 C170.062435,95.7504747 174.573361,105 184.87513,105 C194.989594,105 200,96.5446259 200,84.3209267 C200,73.4208128 195.848075,64 185.171696,64 Z M185.12487,97.9460691 C181.519251,97.9460691 179.318418,93.6171667 179.380853,84.5545006 C179.318418,75.3205469 181.644121,70.9916445 185.062435,70.9916445 C188.777315,70.9916445 190.665973,75.6164071 190.665973,84.4454994 C190.665973,93.4458792 188.71488,97.9460691 185.12487,97.9460691 Z"
                                  id="Fill-3"></path>
                            <path d="M218.179594,64 C207.93493,64 203,73.0626662 203,84.5545006 C203.046851,95.7504747 207.575742,105 217.882874,105 C228.002603,105 233,96.5446259 233,84.3209267 C233,73.4208128 228.86153,64 218.179594,64 Z M218.117126,97.9460691 C214.525247,97.9460691 212.323269,93.6171667 212.385737,84.5545006 C212.323269,75.3205469 214.650182,70.9916445 218.054659,70.9916445 C221.78709,70.9916445 223.676731,75.6164071 223.676731,84.4454994 C223.676731,93.4458792 221.724623,97.9460691 218.117126,97.9460691 Z"
                                  id="Fill-4"></path>
                        </svg>
                    </h4>
                </div>
                <div class="modal-body">
                    <!--AllCosts-->
                    <div class="CostWrap">
                    ' . $priceBreakDownHtml . '
                    </div>
                    <!--AllCosts-->
                </div>
            </div>
        </div>
    </div>';

		print $html;

	}

	/**
	 * @param $productData
	 * @param string $someHtml e.g. data-toggle="modal" data-target="#ModalCheckAvailability" or any link like href="/testit.php"
	 *
	 * @return string
	 */
	public function getProductPriceBreakdownHtml( $productData, $someHtml='', $withoutOrderBtn = false) {
		$currency   = getCurrencySymbol( $productData['currency_unit'] );
		$monthlyFee = convertToEuPrice( $productData['monthly_fee']['value'] );
		list( $advPrice, $monthDurationPromo, $firstYearPrice ) = $this->getPriceInfo( $productData, true );
		if ( $monthDurationPromo == '&nbsp;' ) {
			$monthDurationPromo = pll__( 'Monthly promo price' );
		}

		$actPrice     = $this->getActivationOrInstPriceHtml( $productData['price'], 'activation', '', true, false );
		$actPriceHtml = $this->getActOrInstPriceBreakDownHtml( $actPrice, 'activation', $currency );

		$instPrice     = $this->getActivationOrInstPriceHtml( $productData['price'], 'installation_full', '', true, false );
		$instPriceHtml = $this->getActOrInstPriceBreakDownHtml( $instPrice, 'installation_full', $currency );

		$advHtml = '';
		if ( ! empty( $productData['advantage'] ) ) {
			$advPrice = "-" . $currency . ' ' . convertToEuPrice( $advPrice );

			$advHtml = '<li><div class="total-advantage">
                            ' . pll__( 'Total advantage' ) . '<span class="cost-price">' . $advPrice . '</span>
                            </div>
                       </li>';
		}

		$monthlyPromoPriceHtml = '';

		if ( ! empty( $productData['price']['monthly_promo'] ) &&
		     ( $productData['price']['monthly_promo'] != $productData['price']['monthly'] ) ) {
			$monthlyPromoPriceHtml = '<li>' . sprintf( pll__( 'First %d months' ), $monthDurationPromo ) . '<span class="cost-price">' . $currency . ' ' . convertToEuPrice( $productData['price']['monthly_promo'] ) . '</span></li>';
		}

		$orderBtn = '';
		if(!$withoutOrderBtn) {
			$orderBtn = '<a class="btn btn-primary all-caps" ' . $someHtml . '>' . pll__( 'configure your pack' ) . '</a>';
		}

		$html = '<div class="AboutAllCosts">
                    <div class="MonthlyCost">
                        <h5>' . pll__( 'Costs monthly' ) . '</h5>
                        <ul class="list-unstyled">
                            <li>' . $productData['product_name'] . '<span class="cost-price">' . $currency . ' ' . $monthlyFee . '</span></li>
                            ' . $monthlyPromoPriceHtml . '
                        </ul>
                    </div>
                    <div class="MonthlyCost FirstCost">
                        <h5>' . pll__( 'First costs' ) . '</h5>
                        <ul class="list-unstyled">
                            ' . $actPriceHtml . $instPriceHtml . '
                        </ul>
                    </div>
                    <div class="MonthlyCost CostAdvantage">
                        <ul class="list-unstyled">
                            ' . $advHtml . '
                            <li>
                                <div class="yearly-advantage">
                                    ' . pll__( 'Total first year' ) . '<span class="cost-price">' . $currency . ' ' . convertToEuPrice( $firstYearPrice ) . '</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                    '.$orderBtn.'
                </div>';

		return $html;
	}

	/**
	 * @param array $prd
	 *
	 * @return string
	 */
	public function getTitleSection( array $prd ) {
		$titleSec = '<h4>' . $prd['product_name'] . '</h4>
                     <p class="slogan">' . $prd['tagline'] . '</p>';

		return $titleSec;
	}

	public function getLogoSection( array $prd ) {
		$logoSec = '<div class="dealLogo">
                        <img src="' . $prd['logo']['200x140']->transparent->color . '" alt="' . $prd['product_name'] . '">
                    </div>';

		return $logoSec;
	}

	public function getCustomerRatingSection( $prd ) {
		$custRatSec = '';
		if ( (float) $prd['score'] > 0 ) {
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
	 *
	 * @return string
	 */
	public function getBadgeSection( $badgeTxt ) {
		$revSec = '<div class="bestReviewBadge">
                        <span>' . pll__( 'BEST' ) . '</span>
                        <span class="bold">' . pll__( $badgeTxt ) . '</span>
                   </div>';

		return $revSec;
	}

	public function getProductDetailSection( $prd, $servicesHtml, $includeBadge = false, $badgeTxt = '' ) {
		$detailsSec = '<div class="dealDetails">';

		if ( $includeBadge && ! empty( $badgeTxt ) ) {
			$detailsSec = $this->getBadgeSection( $badgeTxt );
		}

		$detailsSec .= $this->getCustomerRatingSection( $prd ) .
		               $this->getLogoSection( $prd ) .
		               $this->getTitleSection( $prd ) .
		               $this->getServiceIconsSection( $servicesHtml );

		$detailsSec .= '</div>';

		return $detailsSec;
	}

	/**
	 * @param array $prd
	 * @param boolean $onlyNumericData
	 *
	 * @return array
	 */
	public function getPriceInfo( array $prd, $onlyNumericData = false ) {
		$advPrice = '&nbsp;';

		if ( ! empty( $prd['advantage'] ) ) {
			if ( $onlyNumericData ) {
				$advPrice = $prd['advantage'];
			} else {
				$advPrice = "-" . $prd['advantage'] . getCurrencySymbol( $prd['currency_unit'] ) . ' ' . pll__( 'advantage' );
			}
		}

		$monthDurationPromo = '&nbsp;';
		//sprintf
		if ( ! empty( $prd['monthly_promo_duration'] ) ) {
			if ( $onlyNumericData ) {
				$monthDurationPromo = $prd['monthly_promo_duration'];
			} else {
				$monthDurationPromo = sprintf( pll__( 'the first %d months' ), $prd['monthly_promo_duration'] );
			}
		}

		$firstYearPrice = '';
		if ( intval( $prd['year_1_promo'] ) > 0 ) {
			if ( $onlyNumericData ) {
				$firstYearPrice = $prd['year_1_promo'];
			} else {
				$firstYearPrice = getCurrencySymbol( $prd['currency_unit'] ) . ' ' . intval( $prd['year_1_promo'] );
				$firstYearPrice = $firstYearPrice . ' ' . pll__( 'the first year' );
			}
		}

		return array( $advPrice, $monthDurationPromo, $firstYearPrice );
	}

	/**
	 * Wrapper for Aanbieders API getProducts method
	 *
	 * @param array $params
	 * @param array|int|string $productId
	 *
	 * @return array
	 */
	public function getProducts( array $params, $productId = null, $enableCache = true, $cacheDurationSeconds = 600 ) {
	    if(defined('PRODUCT_API_CACHE_DURATION')) {
	        $cacheDurationSeconds = PRODUCT_API_CACHE_DURATION;
        }
        if ( is_string( $productId ) && ! is_numeric( $productId ) ) {
            //make it part of params instead of passing directly to the API
            $params['productid'] = $productId;
            $productId           = null;
        }

        //generate key from params to store in cache
        displayParams($params);
        $start = getStartTime();
        $displayText = "Time API (Product) inside getProducts";
        if ($enableCache && !isset($_GET['no_cache'])) {
            $keyParams = $params + $params['detaillevel'] + ['indv_product_id' => $productId];
            $cacheKey = md5(implode(",", $keyParams) . $_SERVER['REQUEST_URI']) . ":getProducts";

            $result = get_transient($cacheKey);

            if($result === false || empty($result)) {
                $result = $this->anbApi->getProducts( $params, $productId );
                set_transient($cacheKey, $result, $cacheDurationSeconds);
            } else {
                $displayText = "Time Cached API Data (Product) inside getProducts";
            }
        } else {
            $result = $this->anbApi->getProducts( $params, $productId );
        }
        $finish = getEndTime();
        displayCallTime($start, $finish, $displayText);

		return $result;
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
     * @param null $options
     * @param null $groupOptions
     * @param boolean $isRecommendedRequired
     * @return array
     */
	public function prepareProductRecommendedOptions ($options = null, $groupOptions = null, $isRecommendedRequired = true)
    {

        $groupOptionsArray = $optionsArray = [];
        $excludeFromOptions = [];
        $minFee = 0;

        foreach ($groupOptions as $groupOption) {
        	if(is_array($groupOption)) {
		        $groupOption = (object)$groupOption;
	        }
            if ($groupOption->is_recommended || $isRecommendedRequired === false) {

                $groupOptionsArray['groupOptions'][$groupOption->optiongroup_id] = [
                    'name' => $groupOption->texts->name,
                    'description' => $groupOption->texts->description,
                    'banner' => $groupOption->links->banner,
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

                        $excludeFromOptions[] = $optionSpecification['option_id'];
                        $groupOptionsArray['groupOptions'][$groupOption->optiongroup_id]['options'][] = [
                            'id' => $optionSpecification['option_id'],
                            'price' => $optionSpecification['price'],
                            'name' => $optionSpecification['texts']['name'],
                            'description' => $optionSpecification['texts']['description'],
                            'banner' => $optionSpecification['links']['banner'],
                        ];
                    }
                }
                $groupOptionsArray['groupOptions'][$groupOption->optiongroup_id]['minPrice'] = $minFee;
            }

        }

        foreach ($options as $listOption) {
        	if(is_array($listOption)) {
		        $listOption = (object)$listOption;
	        }
            if (($listOption->is_recommended || $isRecommendedRequired === false) && !in_array($listOption->option_id, $excludeFromOptions)) {

                $optionsArray['options'][$listOption->option_id] = [
                	'id' => $listOption->option_id,
                    'price' => $listOption->price,
                    'name' => $listOption->texts->name,
                    'description' => $listOption->texts->description,
	                'banner' => $listOption->links->banner
                ];
            }

        }

        return array_merge($groupOptionsArray , $optionsArray);
    }

}
