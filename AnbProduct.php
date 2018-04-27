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
			'detaillevel' => [ 'supplier', 'logo', 'services', 'price', 'reviews', 'texts', 'promotions', 'core_features' ],
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
			$priceHtml = $this->getPriceHtml( $prd, true );

			//Promotions, Installation/Activation HTML
			//display installation and activation price
			list($promotionHtml, $totalOnetimeCost) = $this->getPromoInternalSection( $prd, true, true );//True here will drop promotions


			list( $advPrice, $monthDurationPromo, $firstYearPrice ) = $this->getPriceInfo( $prd );
			//Commented as product info link is now not there, kept as it has useful information
			//<a href="/' . pll__( 'brands' ) . '/' . $prd['supplier_slug'] . '/' . $prd['product_slug'] . '" class="btn btn-primary">' . pll__( 'Info and options' ) . '</a>
			$anbComp = wpal_create_instance( \AnbSearch\AnbCompare::class );

			$parentSegment = getSectorOnCats( $cats );
			$checkoutPageLink = '/' . $parentSegment . '/' . pll__( 'checkout' );

            $forceCheckAvailability = false;

            $missingZipClass = '';

            if(empty($_GET['zip'])) {
                $forceCheckAvailability = true;
                $missingZipClass = 'missing-zip';
            }

            list(, , , , $toCartLinkHtml) = $this->getToCartAnchorHtml($parentSegment, $prd['product_id'], $prd['supplier_id'], $prd['sg'], $prd['producttype'], $forceCheckAvailability);

			/*$toCartLinkHtml = 'href="' . $checkoutPageLink . '?product_to_cart&product_id='.$prd['product_id'] .
			                  '&provider_id=' . $prd['supplier_id'] . '&sg=' . $prd['sg'] . '&producttype=' . $prd['producttype'] . '"';*/
			$toCartLinkHtml = '<a '.$toCartLinkHtml.' class="link block-link '.$missingZipClass.'">' . pll__( 'Order Now' ) . '</a>';
			$btnHtml = '<div class="buttonWrapper">
                            <a href="/' . pll__( 'brands' ) . '/' . $prd['supplier_slug'] . '/' . $prd['product_slug'] . '" class="btn btn-primary ">' . pll__( 'Info and options' ) . '</a>
                            '.$toCartLinkHtml.'
                        </div>';

			$infoOptionsHtml = '<div class="lastOrder" style="height: 37px;">
                                    <p>'.decorateLatestOrderByProduct($prd->product_id).'</p>
                                </div>' . $btnHtml;
			//echo $this->priceSection( '', '', '', 'dealPrice last', $infoOptionsHtml, false, $productData );

			$navContent .= '<div class="col-md-4 offer offer-col ' . $boxClass . '">
                                ' . $this->getProductDetailSection( $prd, $servicesHtml ) .
			               $this->priceSection( $priceHtml, $monthDurationPromo, $firstYearPrice, 'dealPrice', '', '', $prd, true ) .
			               $this->getPromoSection( $promotionHtml, $prd['advantage'], 'dealFeatures', '', true, $totalOnetimeCost ) .
			                 '<div class="packageInfo">' .
                                '<h6>' . pll__('Features') . '</h6>' .
								$anbComp->getServiceDetail( $products[$idx] ) .
							 '</div>' .
			                 $this->priceSection( '', '', '', 'dealPrice last', $infoOptionsHtml, false, $prd ) .
			               '</div>';
		}
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

	function getActivationOrInstPriceHtml( $priceDetailArray, $key, $currencySymbol = '', $onlyArray = false, $withFirstTerm = true, &$totalOnetimeAmount = 0.00 ) {
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
			$oldPriceArr = formatPriceInParts($priceDetailArray[ $key ], 2, $currencySymbol);
			$oldPriceHtml = '<span class="cutPrice"><span class="amount">'.$oldPriceArr['currency'].$oldPriceArr['price'].'</span><span class="cents">'.$oldPriceArr['cents'].'</span></span>';
			$actualPriceArr = formatPriceInParts($priceDetailArray[ $key ], 2, $currencySymbol);
			$actualPriceHtml = '<span class="bold"><span class="amount">'.$actualPriceArr['currency'].$actualPriceArr['price'].'</span><span class="cents">'.$actualPriceArr['cents'].'</span></span>';
			$promoPriceArr = formatPriceInParts($priceDetailArray[ $key . '_promo' ], 2, $currencySymbol);
			$promoPriceHtml = '<span class="bold"><span class="amount">'.$promoPriceArr['currency'].$promoPriceArr['price'].'</span><span class="cents">'.$promoPriceArr['cents'].'</span></span>';
			if ( $priceDetailArray[ $key . '_promo' ] > 0
			     && $priceDetailArray[ $key . '_promo' ] != $priceDetailArray[ $key ]
			) {//there is a promotional price as well
				$totalOnetimeAmount += $priceDetailArray[ $key . '_promo' ];
				$html .= '<li class="prominent">' . pll__( $firstTermLbl ) . ' ' . $promoPriceHtml .
				         ' ' . $oldPriceHtml . '</li>';
			} elseif ( $priceDetailArray[ $key . '_promo' ] == 0 ) {
				$html .= '<li class="prominent">' . pll__( 'Free ' . $firstTerm ) . ' ' . $oldPriceHtml . '</li>';
			} else {
				$totalOnetimeAmount += $priceDetailArray[ $key ];
				$html .= '<li class="bulletTick">' . pll__( $firstTermLbl ) . ' ' . $actualPriceHtml . '</li>';
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

		$promoPriceHtml = ( ! empty( $priceArray[ $key . '_promo' ] ) && $priceArray[ $key . '_promo' ] != $priceArray[ $key . '_actual' ] ) ? '<span class="saving-price">' . formatPrice($priceArray[ $key ], 2, $priceArray[ $key . '_actual' ]) . '</span>' : '';
		$html           = '<li>' . pll__( ucfirst( $firstTerm ) . ' cost' ) . '
					' . $promoPriceHtml . '
                    <span class="cost-price">' . formatPrice($priceArray[ $key ], 2, $currencySymbol) . '</span>
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
			$servicesHtml .= '<li class="wifi">
                                <i class="service-icons wifi"></i>
                              </li>';
		}
		if ( strpos( $prdOrPckTypes, "gsm" ) !== false ) {
			$servicesHtml .= '<li class="mobile">
                                <i class="service-icons mobile"></i>
                              </li>';
		}
		if ( strpos( $prdOrPckTypes, "tel" ) !== false ) {
			$servicesHtml .= '<li class="phone">
                                <i class="service-icons phone"></i>
                              </li>';
		}
		if ( strpos( $prdOrPckTypes, "tv" ) !== false ) {
			$servicesHtml .= '<li class="tv">
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
		$data['sg']            = $product->segment;
		$data['last_update']   = $product->last_update;
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
        $data['commission']  = $product->commission;
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
	public function getPriceHtml( array $prd, $withCalcHtml = false ) {
		$priceHtml = '';
		$calcHtml = '';

		if ( $withCalcHtml ) {
			$href = "action=ajaxProductPriceBreakdownHtml&pid={$prd['product_id']}&prt={$prd['producttype']}";

			$calcHtml = '<span class="calc">
                    <a href="'.$href.'" data-toggle="modal" data-target="#calcPbsModal">
                        <i class="custom-icons calc"></i>
                    </a>
                 </span>';
		}

		if ( $prd['monthly_price_chunk']['cents'] == "000" ) {
			$prd['monthly_price_chunk']['cents'] = "00";
		}

		if ( isset( $prd['monthly_promo_price_chunk'] ) ) {
			$priceHtml .= '<div class="oldPrice">
                                <span class="amount">' . getCurrencySymbol($prd['currency_unit']) . $prd['monthly_price_chunk']['price'] . '</span>';
			if ( isset( $prd['monthly_price_chunk']['cents'] ) && ! empty( $prd['monthly_price_chunk']['cents'] ) ) {
				$priceHtml .= '<span class="cents">' . substr($prd['monthly_price_chunk']['cents'], 0, 2) . '</span>';
			}
			$priceHtml .= '</div>';

			$priceHtml .= '<div class="newPrice">
                                <span class="amount">' . $prd['monthly_promo_price_chunk']['price'];
			if ( isset( $prd['monthly_price_chunk']['cents'] ) && ! empty( $prd['monthly_price_chunk']['cents'] ) ) {
				$priceHtml .= '<span class="cents">' . substr($prd['monthly_promo_price_chunk']['cents'], 0, 2) . '</span>';
			}
			$priceHtml .= '<span class="recursion">/mth</span>
						   '.$calcHtml.'
						</span>
                       </div>';
		} else {
            $priceHtml .= '<div class="oldPrice"></div>';
			$priceHtml .= '<div class="newPrice">
                                <span class="amount">' . $prd['monthly_price_chunk']['price'];
			if ( isset( $prd['monthly_price_chunk']['cents'] ) && ! empty( $prd['monthly_price_chunk']['cents'] ) ) {
				$priceHtml .= '<span class="cents">' . substr($prd['monthly_price_chunk']['cents'], 0, 2) . '</span>';
			}
			$priceHtml .= '<span class="recursion">/mth</span>
						   '.$calcHtml.'
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
	public function getPromoInternalSection( array $prd, $withoutPromoList = false, $withTotalOnetimeCost = false ) {
		$promotionHtml = '';
		$totalOnetimeAmount = 0;
		$promotionHtml .= $this->getActivationOrInstPriceHtml( $prd['price'], 'installation_full', getCurrencySymbol( $prd['currency_unit'] ), false, true, $totalOnetimeAmount );
		$promotionHtml .= $this->getActivationOrInstPriceHtml( $prd['price'], 'activation', getCurrencySymbol( $prd['currency_unit'] ), false, true, $totalOnetimeAmount );
		$promotionHtml .= $this->getActivationOrInstPriceHtml( $prd['price'], 'modem', getCurrencySymbol( $prd['currency_unit'] ), false, true, $totalOnetimeAmount );

		//will block other promtions except from one-off costs like installation and activation
		if ( ! $withoutPromoList ) {
			$promotionHtml .= $this->getPromoHtml( $prd );
		}

		return ($withTotalOnetimeCost) ? [$promotionHtml, $totalOnetimeAmount] : $promotionHtml;
	}

	public function getServiceIconsSection( $servicesHtml ) {
		$serviceSec = '<div class="services">
                            <ul class="list-unstyled list-inline">
                                ' . $servicesHtml . '
                            </ul>
                         </div>';

		return $serviceSec;
	}

	public function getPromoSection( $promotionHtml, $advPrice, $cssClass = 'dealFeatures', $appendHtml = '', $withOnetimeCostLabel = false, $oneTimeTotalCost = 0 ) {
		$oneTimeCostLabel = '';
		if($withOnetimeCostLabel) {
			$oneTimeCostLabel = '<h6>'.pll__('One-time costs').'</h6>';
		}
		$advHtml = '';
		if(is_numeric($advPrice) && $advPrice > 0) {

			$advHtml     = $this->getTotalAdvHtml( $advPrice );
		}

		$promoSec = '<div class="' . $cssClass . '">
                        <div class="extras">
                        	'.$oneTimeCostLabel.'
                            <ul class="list-unstyled">
                                ' . $promotionHtml . '
                            </ul>
                        </div>
                        ' . $advHtml . '
                        ' . $appendHtml . '
                    </div>';

		return $promoSec;
	}

	public function priceSection( $priceHtml, $monthDurationPromo, $firstYearPrice, $cssClass = 'dealPrice', $appendHtml = '', $calcHtml = '', $productData = [], $withoutYearlyCost = false ) {
		$prominentClass = '';
		if($firstYearPrice) {
			$prominentClass = 'class="prominent"';
		}

		$yearlyPriceHtml = '';
		if($withoutYearlyCost) {
			$promoPrice = $productData['price']['monthly'] - $productData['price']['monthly_promo'];
			if($productData['monthly_promo_duration'] > 0 && $promoPrice > 0) {
				$formatedPromoPrice = getCurrencySymbol($productData['currency_unit']) . ' ' . formatPrice($promoPrice, 2, '');
				$yearlyPriceHtml = "<li $prominentClass>".sprintf(pll__('%s discount for %d months'), $formatedPromoPrice, $productData['monthly_promo_duration']) ."</li>";
			} else {
				$yearlyPriceHtml = "<li></li>";
			}
		} else {
			$yearlyPriceHtml = '<li>' . $monthDurationPromo . '</li>
                                <li '.$prominentClass.'>' . $firstYearPrice
			                   . $calcHtml .
			                   '</li>';
		}

		if(!empty($firstYearPrice) || !empty($monthDurationPromo) || !empty($calcHtml)) {
			$priceInfoHtml = '<div class="priceInfo">
                            <ul class="list-unstyled">
                                '.$yearlyPriceHtml.'
                            </ul>
                        </div>';
		}

		$priceSec = '<div class="' . $cssClass . '">
                        ' . $priceHtml . '
                        ' . $priceInfoHtml . '
                        ' . html_entity_decode( $appendHtml ) . '
                     </div>';

		return $priceSec;
	}

	/**
	 * @param $productData
	 */
	public function calculatorPopup( $productData ) {
		$html               = "<div class='modal borderLess fade' id='calcBreakdown{$productData['product_id']}'  tabindex='-1' role='dialog' aria-labelledby='calcBreakdownLabel'>";
		/** @var \AnbSearch\AnbCompare $anbComp */
		/*$anbComp = wpal_create_instance( \AnbSearch\AnbCompare::class );
		$priceBreakdown = $this->getProductPriceBreakdownHtmlApi( [
			'lang_mod' => $anbComp->getCurrentLang(),
			'pid' => $productData['product_id'],
			'prt' => $productData['producttype']
		]);*/

		$priceBreakdownHtml = $this->getProductPriceBreakdownHtml($productData);

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
                    ' . $priceBreakdownHtml . '
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
			$advHtml = '<li><div class="total-advantage">
                            ' . pll__( 'Total advantage' ) . '<span class="cost-price">' . formatPrice($advPrice, 2, $currency) . '</span>
                            </div>
                       </li>';
		}

		$monthlyPromoPriceHtml = '';

		if ( ! empty( $productData['price']['monthly_promo'] ) &&
		     ( $productData['price']['monthly_promo'] != $productData['price']['monthly'] ) ) {
			$monthlyPromoPriceHtml = '<li>' . sprintf( pll__( 'First %d months' ), $monthDurationPromo ) . '<span class="cost-price">' . formatPrice($productData['price']['monthly_promo'], 2, $currency)  . '</span></li>';
		}

		$orderBtn = '';
		if(!$withoutOrderBtn) {
			$orderBtn = $someHtml;
		}

		$html = '<div class="AboutAllCosts">
                    <div class="MonthlyCost">
                        <h5>' . pll__( 'Costs monthly' ) . '</h5>
                        <ul class="list-unstyled">
                            <li>' . $productData['product_name'] . '<span class="cost-price">' . formatPrice($monthlyFee, 2, $currency) . '</span></li>
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
                                    ' . pll__( 'Total first year' ) . '<span class="cost-price">' . formatPrice($firstYearPrice, 2, $currency) . '</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                    '.$orderBtn.'
                </div>';

		return $html;
	}

    /**
     * This method is same as getProductPriceBreakdownHtml but it'll call API to render the break-down instead of what is available in product data
     * E.g. Link for API: https://www.aanbieders.be/rpc?&lang_mod=nl&action=load_calc_json&pid=2855&prt=packs&opt[]‌=280&opt[]‌=425&it=full&extra_pid[]=mobile|643
     *
     * @param array $apiParams these will be API Params
     * @param string $someHtml
     * @param bool $withoutOrderBtn
     * @return string
     */
    public function getProductPriceBreakdownHtmlApi( array $apiParams, $someHtml='', $withoutOrderBtn = false, $displayFirstProductOnly = false) {
	    //if language is missing get that automatically
	    if(!isset($apiParams['lang_mod']) || empty($apiParams['lang_mod'])) {
		    /** @var \AnbSearch\AnbCompare $anbComp */
		    $anbComp = wpal_create_instance( \AnbSearch\AnbCompare::class );
		    $apiParams['lang_mod'] = $anbComp->getCurrentLang();
	    }

	    $apiParams['opt'] = array_filter($apiParams['opt']);
	    $apiParams['extra_pid'] = array_filter($apiParams['extra_pid']);

        $html = '';
        $apiParamsHtml = http_build_query($apiParams, "&");
        $apiUrl = AB_PRICE_BREAKDOWN_URL . '&' . $apiParamsHtml;

        $apiRes = file_get_contents($apiUrl);

        $totalMonthly = '';
        $totalYearly = '';
        $totalAdv = '';
        $totalAdvPrice = 0;
        $grandTotal = 0;
        $productCount = 0;
	    $monthlyTotal = 0;
	    $yearlyTotal = 0;
	    $advTotal = 0;

        if($apiRes) {
            $apiRes = json_decode($apiRes);

            $orderBtn = '';
            if(!$withoutOrderBtn) {
                $orderBtn = $someHtml;
            }

            $html = '<div class="AboutAllCosts">';
            foreach($apiRes as $key => $priceSec) {
                $totalMonthly = $priceSec->monthly_costs->subtotal->display_value;
                $totalYearly = $priceSec->total->display_value;
                $totalAdv = $priceSec->total_discount->display_value;
                $totalAdvPrice = $priceSec->total_discount->value;
	            $monthlyTotal += $priceSec->monthly_costs->subtotal->value;
                $yearlyTotal += $priceSec->total->value;
                $grandTotal += $priceSec->monthly_costs->subtotal->value;
                $advTotal += $priceSec->total_discount->value;

                //either display only first product or display them all, default is displaying them all
                if(($productCount === 0 && $displayFirstProductOnly === true) || $displayFirstProductOnly === false) {
                    foreach($priceSec as $pKey => $pVal) {
                        if(strpos($pKey, 'total') !== false) {
                            break;//don't include the totals in loop
                        }
                        $html .= '<div class="MonthlyCost">';
                        $html .= '<h5>' . $pVal->label . '</h5>';
                        $html .= '<ul class="list-unstyled">';
                        foreach($pVal->lines as $lineKey => $lineVal) {
                            $priceDisplayVal = $lineVal->product->display_value;
                            $extraClass = '';
                            if($lineVal->product->value == 0) {
                                $extraClass = 'class="prominent"';
                                $priceDisplayVal = pll__('Free');
                            }
                            $html .= '<li '.$extraClass.'>' . $lineVal->label . '<span class="cost-price">' . $priceDisplayVal . '</span></li>';
                        }
                        $html .= '</ul>';
                        $html .= '</div>';
                    }
                }

                $productCount++;
            }

            $advHtml = '';

            if($totalAdvPrice < 0) {
                $advHtml = '<li><div class="total-advantage">
                            ' . pll__( 'Total advantage' ) . '<span class="cost-price">' . formatPrice($advTotal) . '</span>
                            </div></li>';
            }

            $html .=     '<div class="MonthlyCost CostAdvantage">
                            <ul class="list-unstyled">
                                '.$advHtml.'
                                <li>
                                    <div class="total-monthly">
                                        ' . pll__( 'Total monthly' ) . '<span class="cost-price">' . formatPrice($monthlyTotal) . '</span>
                                    </div>
                                </li>
                                <li>
                                    <div class="yearly-advantage">
                                        ' . pll__( 'Total first year' ) . '<span class="cost-price">' . formatPrice($yearlyTotal) . '</span>
                                    </div>
                                </li>
                            </ul>
                          </div>';
            $html .= $orderBtn.
                '</div>';
        }

        return [
        	'html' => $html,
	        'monthly' => $totalMonthly,
	        'first_year' => $totalYearly,
	        'grand_total' => $grandTotal,
	        'yearly_total' => $yearlyTotal,
	        'monthly_total' => $monthlyTotal
        ];
    }

	/**
	 * This method is same as getProductPriceBreakdownHtmlApi, but it'll generate HTML in a different organized manner which is more readable,
	 * another difference is it'll generate first product by default and loop over the child products inside that to display them in a specific place
	 * E.g. Link for API: https://www.aanbieders.be/rpc?&lang_mod=nl&action=load_calc_json&pid=2855&prt=packs&opt[]‌=280&opt[]‌=425&it=full&extra_pid[]=mobile|643
	 *
	 * @param array $apiParams these will be API Params
	 * @param string $someHtml
	 * @param bool $withoutOrderBtn
	 * @return string
	 */
	public function getPbsOrganizedHtmlApi( array $apiParams, $someHtml='', $withoutOrderBtn = false, $displayFirstProductOnly = true) {
		//if language is missing get that automatically
		if(!isset($apiParams['lang_mod']) || empty($apiParams['lang_mod'])) {
			/** @var \AnbSearch\AnbCompare $anbComp */
			$anbComp = wpal_create_instance( \AnbSearch\AnbCompare::class );
			$apiParams['lang_mod'] = $anbComp->getCurrentLang();
		}

		$apiParams['opt'] = array_filter($apiParams['opt']);
		$apiParams['extra_pid'] = array_filter($apiParams['extra_pid']);

		$html = '';
		$apiParamsHtml = http_build_query($apiParams, "&");
		$apiUrl = AB_PRICE_BREAKDOWN_URL . '&' . $apiParamsHtml;

		if($_GET['debug']) {
			echo "<pre>$apiUrl</pre>";;
		}

		$apiRes = file_get_contents($apiUrl);

		$totalMonthly = '';
		$totalYearly = '';
		$totalAdv = '';
		$totalAdvPrice = 0;
		$grandTotal = 0;
		$productCount = 0;
		$monthlyTotal = 0;
		$yearlyTotal = 0;
		$advTotal = 0;

		$monthlyDisc = 0;
		$yearlyDisc = 0;
		$currencyUnit = '';

		$yearlyAdvCollection = [];
		$sectionsHtml = [];//0 for monthly, 1 for onetime, 2 for yearly

		if($apiRes) {
			$apiRes = json_decode($apiRes);

			$orderBtn = '';
			if(!$withoutOrderBtn) {
				$orderBtn = $someHtml;
			}

			$html .= '<div class="calculationPanel">';
			//Generate the main HTML only for main/base product
			$oneTimeHtml = '';
			$dynamicHtml = '';//Just to be used as container to combine all HTML
			foreach($apiRes as $key => $priceSec) {
				$currencyUnit  = $priceSec->total->unit;
				$totalMonthly  = $priceSec->monthly_costs->subtotal->display_value;
				$totalYearly   = $priceSec->total->display_value;
				$totalAdv      = $priceSec->total_discount->display_value;
				$totalAdvPrice = $priceSec->total_discount->value;
				$monthlyTotal  += $priceSec->monthly_costs->subtotal->value;
				$monthlyDisc   += abs($priceSec->monthly_costs->subtotal_discount->value);
				$oneoffTotal   += $priceSec->oneoff_costs->subtotal->value;
				$oneoffDisc    += abs($priceSec->oneoff_costs->subtotal_discount->value);
				$yearlyTotal   += $priceSec->total->value;
				$yearlyDisc    += abs( $priceSpec->total_discount );//if number is negative convert that to +ve
				$grandTotal    += $priceSec->monthly_costs->subtotal->value;
				$advTotal      += abs($priceSec->total_discount->value);

				list( $monthlyHtml, $yearlyAdvCollection ) = $this->generatePbsSectionHtml(
					$dynamicHtml,
					'pbs-monthly',
					$priceSec->monthly_costs,
					$productCount,
					$monthlyTotal,
					$yearlyAdvCollection,
					$sectionsHtml,
					pll__('Monthly costs'),
					pll__('Monthly total'),
					pll__('First month'),
					pll__('PBS: Monthly total tooltip text')
				);

				$dynamicHtml .= $monthlyHtml;

				if(isset($priceSec->oneoff_costs)) {
					list( $oneoffHtml, $yearlyAdvCollection ) = $this->generatePbsSectionHtml( $dynamicHtml, 'pbs-oneoff', $priceSec->oneoff_costs, $productCount,
						$oneoffTotal, $yearlyAdvCollection, $sectionsHtml, pll__('One-time costs'), pll__('One-time total') );
					$dynamicHtml .= $oneoffHtml;
				}

				$productCount++;
			}

			//Now use sectionHtml array to render the final HTML at this stage we can unset $dynamicHtml as that's no more required
			unset($dynamicHtml);
			$html .= $sectionsHtml['pbs-monthly'];
			$html .= $sectionsHtml['pbs-oneoff'];

			/*$html .= '<div class="MonthlyCost">';
			$html .= '<h5>' . $pVal->label . '</h5>';
			$html .= '<ul class="list-unstyled">';
			$html .= $oneTimeHtml;
			$html .= '</ul>';
			$html .= '</div>';*/

			/*$advHtml = '';

			if($totalAdvPrice < 0) {
				$advHtml = '<li><div class="total-advantage">
                            ' . pll__( 'Total advantage' ) . '<span class="cost-price">' . formatPrice($advTotal) . '</span>
                            </div></li>';
			}*/
			if(!empty($yearlyAdvCollection)) {
				list($yearlyHtml, $yearlyAdvTotal) = $this->generatePbsYearlyBreakdownHtml( $yearlyAdvCollection, $currencyUnit );
				if($yearlyAdvTotal != 0) {
					$html .= $yearlyHtml;
				}
			}

			//$advHtml was part of it now removing it
			/*$html .=     '<div class="MonthlyCost CostAdvantage">
                            <ul class="list-unstyled">
                                '.$yearlyAdvHtml.'
                                <li>
                                    <div class="yearly-advantage">
                                        ' . pll__( 'Total first year' ) . '<span class="cost-price">' . formatPrice($yearlyTotal) . '</span>
                                    </div>
                                </li>
                            </ul>
                          </div>';*/
			$html .= $orderBtn.
			         '</div>';
		}

		return [
			'html'                  => $html,
			'monthly'               => $totalMonthly,
			'first_year'            => $totalYearly,
			'grand_total'           => $grandTotal,
			'yearly_total'          => $yearlyTotal,
			'monthly_total'         => $monthlyTotal,
			'monthly_disc'          => $monthlyDisc,
			'yearly_disc'           => $yearlyDisc,
			'yearly_adv_collection' => $yearlyAdvCollection,
			'currency_unit'         => $currencyUnit
		];
	}

    function ajaxProductPriceBreakdownHtml() {
        $apiData = [
            'pid' => $_REQUEST['pid'],//product id
            'prt' => $_REQUEST['prt'],//product type like internet, packs or energy
            'it'  => $_REQUEST['it'],//Installation type like full/diy
            'opt' => array_filter($_REQUEST['opt']),//array options
            'extra_pid' => array_filter($_REQUEST['extra_pid']),//array extra PIDs like extra_pid[]=mobile]|643
        ];

        $apiData = array_filter($apiData);//cleaning empty values

        //list($toCartPage) = $this->getToCartAnchorHtml($_POST['parent_segment'], $_POST['product_id'], $_POST['supplier_id']);

        //echo "toCartPage: $toCartPage";

        //$priceBreakdown = $this->getProductPriceBreakdownHtmlApi($apiData);
	    $priceBreakdown = $this->getPbsOrganizedHtmlApi($apiData);

        /*echo '<div class="CostWrap">
            <div class="TotalCostBox">
                <svg class="calculator" height="30px" viewBox="0 0 291 393" fill="#FFF">
                    <path d="M232.806181,0 L58.193819,0 C26.1918543,0 0,26.2144262 0,58.2096279 L0,334.790372 C0,366.80103 26.1918543,393 58.193819,393 L232.806181,393 C264.808146,393 291,366.80103 291,334.790372 L291,58.2096279 C291,26.2144262 264.808146,0 232.806181,0 Z M93.5644116,334.790372 C93.5644116,342.765988 86.9816801,349.350507 78.946421,349.350507 L58.193819,349.350507 C50.1585599,349.350507 43.6376381,342.765988 43.6376381,334.790372 L43.6376381,313.970306 C43.6376381,305.99469 50.1585599,299.410171 58.193819,299.410171 L78.946421,299.410171 C86.9816801,299.410171 93.5644116,305.99469 93.5644116,313.970306 L93.5644116,334.790372 Z M93.5644116,257.816408 C93.5644116,265.838394 86.9816801,272.361087 78.946421,272.361087 L58.193819,272.361087 C50.1585599,272.361087 43.6376381,265.838394 43.6376381,257.816408 L43.6376381,237.042712 C43.6376381,229.082553 50.1585599,222.498034 58.193819,222.498034 L78.946421,222.498034 C86.9816801,222.498034 93.5644116,229.082553 93.5644116,237.042712 L93.5644116,257.816408 Z M93.5644116,180.888815 C93.5644116,188.926257 86.9816801,195.44895 78.946421,195.44895 L58.193819,195.44895 C50.1585599,195.44895 43.6376381,188.926257 43.6376381,180.888815 L43.6376381,160.130575 C43.6376381,152.093133 50.1585599,145.508613 58.193819,145.508613 L78.946421,145.508613 C86.9816801,145.508613 93.5644116,152.093133 93.5644116,160.130575 L93.5644116,180.888815 Z M170.455661,334.790372 C170.455661,342.765988 163.872929,349.350507 155.914932,349.350507 L135.085068,349.350507 C127.127071,349.350507 120.544339,342.765988 120.544339,334.790372 L120.544339,313.970306 C120.544339,305.99469 127.127071,299.410171 135.085068,299.410171 L155.914932,299.410171 C163.872929,299.410171 170.455661,305.99469 170.455661,313.970306 L170.455661,334.790372 Z M170.455661,257.816408 C170.455661,265.838394 163.872929,272.361087 155.914932,272.361087 L135.085068,272.361087 C127.127071,272.361087 120.544339,265.838394 120.544339,257.816408 L120.544339,237.042712 C120.544339,229.082553 127.127071,222.498034 135.085068,222.498034 L155.914932,222.498034 C163.872929,222.498034 170.455661,229.082553 170.455661,237.042712 L170.455661,257.816408 Z M170.455661,180.888815 C170.455661,188.926257 163.872929,195.44895 155.914932,195.44895 L135.085068,195.44895 C127.127071,195.44895 120.544339,188.926257 120.544339,180.888815 L120.544339,160.130575 C120.544339,152.093133 127.127071,145.508613 135.085068,145.508613 L155.914932,145.508613 C163.872929,145.508613 170.455661,152.093133 170.455661,160.130575 L170.455661,180.888815 Z M247.362362,334.790372 C247.362362,342.765988 240.77963,349.350507 232.806181,349.350507 L211.991769,349.350507 C204.01832,349.350507 197.435588,342.765988 197.435588,334.790372 L197.435588,313.970306 C197.435588,305.99469 204.01832,299.410171 211.991769,299.410171 L232.806181,299.410171 C240.77963,299.410171 247.362362,305.99469 247.362362,313.970306 L247.362362,334.790372 Z M247.362362,257.816408 C247.362362,265.838394 240.77963,272.361087 232.806181,272.361087 L211.991769,272.361087 C204.01832,272.361087 197.435588,265.838394 197.435588,257.816408 L197.435588,237.042712 C197.435588,229.082553 204.01832,222.498034 211.991769,222.498034 L232.806181,222.498034 C240.77963,222.498034 247.362362,229.082553 247.362362,237.042712 L247.362362,257.816408 Z M247.362362,180.888815 C247.362362,188.926257 240.77963,195.44895 232.806181,195.44895 L211.991769,195.44895 C204.01832,195.44895 197.435588,188.926257 197.435588,180.888815 L197.435588,160.130575 C197.435588,152.093133 204.01832,145.508613 211.991769,145.508613 L232.806181,145.508613 C240.77963,145.508613 247.362362,152.093133 247.362362,160.130575 L247.362362,180.888815 Z M247.362362,101.920947 C247.362362,109.896563 240.77963,116.465626 232.806181,116.465626 L58.193819,116.465626 C50.2203696,116.465626 43.6376381,109.896563 43.6376381,101.920947 L43.6376381,66.5407457 C43.6376381,58.5651302 50.2203696,51.9806104 58.193819,51.9806104 L232.806181,51.9806104 C240.84144,51.9806104 247.362362,58.5033037 247.362362,66.5407457 L247.362362,101.920947 Z"
                          id="Fill-1"></path>
                    <path d="M151.187305,64 C140.932362,64 136,73.0626662 136,84.5545006 C136.062435,95.7504747 140.635796,105 150.87513,105 C161.052029,105 166,96.5446259 166,84.3209267 C166,73.4208128 161.848075,64 151.187305,64 Z M151.12487,97.9460691 C147.519251,97.9460691 145.334027,93.6171667 145.396462,84.5545006 C145.334027,75.3205469 147.644121,70.9916445 151.062435,70.9916445 C154.777315,70.9916445 156.728408,75.6164071 156.728408,84.4454994 C156.665973,93.4458792 154.71488,97.9460691 151.12487,97.9460691 Z"
                          id="Fill-2"></path>
                    <path d="M185.171696,64 C174.869927,64 170,73.0626662 170,84.5545006 C170.062435,95.7504747 174.573361,105 184.87513,105 C194.989594,105 200,96.5446259 200,84.3209267 C200,73.4208128 195.848075,64 185.171696,64 Z M185.12487,97.9460691 C181.519251,97.9460691 179.318418,93.6171667 179.380853,84.5545006 C179.318418,75.3205469 181.644121,70.9916445 185.062435,70.9916445 C188.777315,70.9916445 190.665973,75.6164071 190.665973,84.4454994 C190.665973,93.4458792 188.71488,97.9460691 185.12487,97.9460691 Z"
                          id="Fill-3"></path>
                    <path d="M218.179594,64 C207.93493,64 203,73.0626662 203,84.5545006 C203.046851,95.7504747 207.575742,105 217.882874,105 C228.002603,105 233,96.5446259 233,84.3209267 C233,73.4208128 228.86153,64 218.179594,64 Z M218.117126,97.9460691 C214.525247,97.9460691 212.323269,93.6171667 212.385737,84.5545006 C212.323269,75.3205469 214.650182,70.9916445 218.054659,70.9916445 C221.78709,70.9916445 223.676731,75.6164071 223.676731,84.4454994 C223.676731,93.4458792 221.724623,97.9460691 218.117126,97.9460691 Z"
                          id="Fill-4"></path>
                </svg>
                <span class="total-price">'.formatPrice($priceBreakdown['monthly_total']).'</span>
            </div>';

            echo $priceBreakdown['html'];
        echo '</div>';*/

	    echo '<div class="newCostCalc">
                <div class="TotalCostBox">
                    <svg class="calculator" height="28px" viewBox="0 0 291 393" fill="#FFF">
                        <path d="M232.806181,0 L58.193819,0 C26.1918543,0 0,26.2144262 0,58.2096279 L0,334.790372 C0,366.80103 26.1918543,393 58.193819,393 L232.806181,393 C264.808146,393 291,366.80103 291,334.790372 L291,58.2096279 C291,26.2144262 264.808146,0 232.806181,0 Z M93.5644116,334.790372 C93.5644116,342.765988 86.9816801,349.350507 78.946421,349.350507 L58.193819,349.350507 C50.1585599,349.350507 43.6376381,342.765988 43.6376381,334.790372 L43.6376381,313.970306 C43.6376381,305.99469 50.1585599,299.410171 58.193819,299.410171 L78.946421,299.410171 C86.9816801,299.410171 93.5644116,305.99469 93.5644116,313.970306 L93.5644116,334.790372 Z M93.5644116,257.816408 C93.5644116,265.838394 86.9816801,272.361087 78.946421,272.361087 L58.193819,272.361087 C50.1585599,272.361087 43.6376381,265.838394 43.6376381,257.816408 L43.6376381,237.042712 C43.6376381,229.082553 50.1585599,222.498034 58.193819,222.498034 L78.946421,222.498034 C86.9816801,222.498034 93.5644116,229.082553 93.5644116,237.042712 L93.5644116,257.816408 Z M93.5644116,180.888815 C93.5644116,188.926257 86.9816801,195.44895 78.946421,195.44895 L58.193819,195.44895 C50.1585599,195.44895 43.6376381,188.926257 43.6376381,180.888815 L43.6376381,160.130575 C43.6376381,152.093133 50.1585599,145.508613 58.193819,145.508613 L78.946421,145.508613 C86.9816801,145.508613 93.5644116,152.093133 93.5644116,160.130575 L93.5644116,180.888815 Z M170.455661,334.790372 C170.455661,342.765988 163.872929,349.350507 155.914932,349.350507 L135.085068,349.350507 C127.127071,349.350507 120.544339,342.765988 120.544339,334.790372 L120.544339,313.970306 C120.544339,305.99469 127.127071,299.410171 135.085068,299.410171 L155.914932,299.410171 C163.872929,299.410171 170.455661,305.99469 170.455661,313.970306 L170.455661,334.790372 Z M170.455661,257.816408 C170.455661,265.838394 163.872929,272.361087 155.914932,272.361087 L135.085068,272.361087 C127.127071,272.361087 120.544339,265.838394 120.544339,257.816408 L120.544339,237.042712 C120.544339,229.082553 127.127071,222.498034 135.085068,222.498034 L155.914932,222.498034 C163.872929,222.498034 170.455661,229.082553 170.455661,237.042712 L170.455661,257.816408 Z M170.455661,180.888815 C170.455661,188.926257 163.872929,195.44895 155.914932,195.44895 L135.085068,195.44895 C127.127071,195.44895 120.544339,188.926257 120.544339,180.888815 L120.544339,160.130575 C120.544339,152.093133 127.127071,145.508613 135.085068,145.508613 L155.914932,145.508613 C163.872929,145.508613 170.455661,152.093133 170.455661,160.130575 L170.455661,180.888815 Z M247.362362,334.790372 C247.362362,342.765988 240.77963,349.350507 232.806181,349.350507 L211.991769,349.350507 C204.01832,349.350507 197.435588,342.765988 197.435588,334.790372 L197.435588,313.970306 C197.435588,305.99469 204.01832,299.410171 211.991769,299.410171 L232.806181,299.410171 C240.77963,299.410171 247.362362,305.99469 247.362362,313.970306 L247.362362,334.790372 Z M247.362362,257.816408 C247.362362,265.838394 240.77963,272.361087 232.806181,272.361087 L211.991769,272.361087 C204.01832,272.361087 197.435588,265.838394 197.435588,257.816408 L197.435588,237.042712 C197.435588,229.082553 204.01832,222.498034 211.991769,222.498034 L232.806181,222.498034 C240.77963,222.498034 247.362362,229.082553 247.362362,237.042712 L247.362362,257.816408 Z M247.362362,180.888815 C247.362362,188.926257 240.77963,195.44895 232.806181,195.44895 L211.991769,195.44895 C204.01832,195.44895 197.435588,188.926257 197.435588,180.888815 L197.435588,160.130575 C197.435588,152.093133 204.01832,145.508613 211.991769,145.508613 L232.806181,145.508613 C240.77963,145.508613 247.362362,152.093133 247.362362,160.130575 L247.362362,180.888815 Z M247.362362,101.920947 C247.362362,109.896563 240.77963,116.465626 232.806181,116.465626 L58.193819,116.465626 C50.2203696,116.465626 43.6376381,109.896563 43.6376381,101.920947 L43.6376381,66.5407457 C43.6376381,58.5651302 50.2203696,51.9806104 58.193819,51.9806104 L232.806181,51.9806104 C240.84144,51.9806104 247.362362,58.5033037 247.362362,66.5407457 L247.362362,101.920947 Z"
                              id="Fill-1"></path>
                        <path d="M151.187305,64 C140.932362,64 136,73.0626662 136,84.5545006 C136.062435,95.7504747 140.635796,105 150.87513,105 C161.052029,105 166,96.5446259 166,84.3209267 C166,73.4208128 161.848075,64 151.187305,64 Z M151.12487,97.9460691 C147.519251,97.9460691 145.334027,93.6171667 145.396462,84.5545006 C145.334027,75.3205469 147.644121,70.9916445 151.062435,70.9916445 C154.777315,70.9916445 156.728408,75.6164071 156.728408,84.4454994 C156.665973,93.4458792 154.71488,97.9460691 151.12487,97.9460691 Z"
                              id="Fill-2"></path>
                        <path d="M185.171696,64 C174.869927,64 170,73.0626662 170,84.5545006 C170.062435,95.7504747 174.573361,105 184.87513,105 C194.989594,105 200,96.5446259 200,84.3209267 C200,73.4208128 195.848075,64 185.171696,64 Z M185.12487,97.9460691 C181.519251,97.9460691 179.318418,93.6171667 179.380853,84.5545006 C179.318418,75.3205469 181.644121,70.9916445 185.062435,70.9916445 C188.777315,70.9916445 190.665973,75.6164071 190.665973,84.4454994 C190.665973,93.4458792 188.71488,97.9460691 185.12487,97.9460691 Z"
                              id="Fill-3"></path>
                        <path d="M218.179594,64 C207.93493,64 203,73.0626662 203,84.5545006 C203.046851,95.7504747 207.575742,105 217.882874,105 C228.002603,105 233,96.5446259 233,84.3209267 C233,73.4208128 228.86153,64 218.179594,64 Z M218.117126,97.9460691 C214.525247,97.9460691 212.323269,93.6171667 212.385737,84.5545006 C212.323269,75.3205469 214.650182,70.9916445 218.054659,70.9916445 C221.78709,70.9916445 223.676731,75.6164071 223.676731,84.4454994 C223.676731,93.4458792 221.724623,97.9460691 218.117126,97.9460691 Z"
                              id="Fill-4"></path>
                    </svg>
                    <div class="totalPriceWrapper">';

	    if ( $priceBreakdown['monthly_disc'] > 0 ):
		    echo '<div class="oldPrice">
                <span class="oldPriceWrapper">
                    <span class="currency">' . $priceBreakdown['currency_unit'] . '</span>
                    <span class="amount">'.formatPrice($priceBreakdown['monthly_disc']+$priceBreakdown['monthly_total'], 2, '', '', false, true).'</span>
                </span>
            </div>';
	    endif;

	    echo '<div class="totalPrice">';
	    $priceParts = formatPriceInParts( $priceBreakdown['monthly_total'], 2, '' );
	    echo '<span class="currency">' . $priceBreakdown['currency_unit'] . '</span>
                <span class="amount">' . $priceParts['price'] . '</span>
                <span class="cents">' . $priceParts['cents'] . '</span>
                <span class="recursion">/mth</span>
                </div></div></div>';
	    echo $priceBreakdown['html'];
	    echo "</div>";

        wp_die();
    }

    function getToCartAnchorHtml($parentSegment, $productId, $supplierId, $sg='', $productType='', $forceCheckAvailability = false) {
        $domain = explode('//', WP_HOME)[1];
        $directLandOrExt = (strpos($_SERVER['HTTP_REFERER'], $domain) === false || empty($_SESSION['product']['zip'])) ? true : false;

        $checkoutPageLink = '/' . ltrim($parentSegment, '/') . '/' . pll__( 'checkout' );
        $toCartLinkHtml = "href='" . $checkoutPageLink."?product_to_cart&product_id=".$productId .
            "&provider_id=" . $supplierId . "&sg=$sg&producttype=$productType'";

	    if ( ( $directLandOrExt && ! isset( $_GET['zip'] ) && ! empty( $_GET['zip'] ) ) || $forceCheckAvailability ) {
		    $toCartLinkHtml = 'data-pid="' . $productId . '" data-sid="' . $supplierId . '" data-sg="' . $sg . '" data-prt="' . $productType . '"';
	    }

	    $toCartInternalLink = $toCartLinkHtml;
	    $justCartLinkHtml = '<a ' . $toCartLinkHtml . ' class="btn btn-primary all-caps">' . pll__( 'configure your pack' ) . '</a>';
	    $oldCartLinkHtml  = '<a ' . $toCartLinkHtml . ' class="btn btn-default all-caps">' . pll__( 'configure your pack' ) . '</a>';
	    $toCartLinkHtml   = '<div class="buttonWrapper">' . $justCartLinkHtml . '</div>';

        return [$toCartLinkHtml, $directLandOrExt, $justCartLinkHtml, $oldCartLinkHtml, $toCartInternalLink];
    }

	/**
	 * @param array $prd
	 *
	 * @return string
	 */
	public function getTitleSection( array $prd, $listView = false ) {
		$titleSec = '<h4>' . $prd['product_name'] . '</h4>
                     <p class="slogan">' . $prd['tagline'] . '</p>';

		if($listView) {
			$titleSec = '<h5>' . $prd['product_name'] . '</h5>';
		}

		return $titleSec;
	}

	public function getLogoSection( array $prd ) {
		$logoSec = '<div class="dealLogo">
                        <img src="' . $prd['logo']['200x140']->transparent->color . '" alt="' . $prd['product_name'] . '">
                    </div>';

		return $logoSec;
	}

	public function getCustomerRatingSection( $prd, $listView = false ) {
		$custRatSec = '';
		if ( (float) $prd['score'] > 0 ) {
			$custRatSec = '<div class="customerRating">
                            <div class="stamp">
                                ' . $prd['score'] . '
                            </div>
                       </div>';

			if($listView) {
				$custRatSec = '<div class="recCustomerRating">
	                                <span class="ratingCount">' . $prd['score'] . '</span>
	                                <span class="labelHolder">'.pll__('Customer Score').'</span>
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
	public function getBadgeSection( $badgeTxt ) {
		$revSec = '<div class="bestReviewBadge">
                        <span>' . pll__( 'BEST' ) . '</span>
                        <span class="bold">' . pll__( $badgeTxt ) . '</span>
                   </div>';

		return $revSec;
	}

	public function getProductDetailSection( $prd, $servicesHtml, $includeBadge = false, $badgeTxt = '', $listView = false ) {
		$detailsSec = '<div class="dealDetails">';

		if ( $includeBadge && ! empty( $badgeTxt ) ) {
			$detailsSec = $this->getBadgeSection( $badgeTxt );
		}

		if($listView) {
			$detailsSec .= $this->getLogoSection( $prd, $listView ) .
			               $this->getTitleSection( $prd,  $listView) .
			               $this->getCustomerRatingSection( $prd, $listView );
		} else {
			$detailsSec .= $this->getCustomerRatingSection( $prd ) .
			               $this->getLogoSection( $prd ) .
			               $this->getTitleSection( $prd ) .
			               $this->getServiceIconsSection( $servicesHtml );
		}

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
		$totalAdv = 0;

		if ( ! empty( $prd['advantage'] ) && $prd['advantage'] > 0 ) {//only include +ve values in advantage
			if ( $onlyNumericData ) {
				$advPrice = $prd['advantage'];
			} else {
				$advPrice = formatPrice($prd['advantage'], 2, getCurrencySymbol( $prd['currency_unit'] )) . ' ' . pll__( 'advantage' );
			}
			$totalAdv = $prd['advantage'];
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
				$firstYearPrice = getCurrencySymbol( $prd['currency_unit'] ) . ' ' . formatPrice(intval( $prd['year_1_promo'] ), 0, '');
				$firstYearPrice = $firstYearPrice . ' ' . pll__( 'the first year' );
			}
		}

		return array( $advPrice, $monthDurationPromo, $firstYearPrice, $totalAdv );
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

	/**
	 * @param $priceSec
	 * @param $productCount
	 * @param array $yearlyAdvCollection This will include the values which are negative or zero, to make a collection of all advantages
	 *
	 * @return string
	 */
	public function getPbsOrganizedHtmlApiPriceSection( $priceSec, $productCount, &$yearlyAdvCollection = [] ) {
		$html = '';
		$htmlArr = [];
		foreach ( $priceSec->lines as $lineKey => $lineVal ) {
			//if some key starts with free then skip it, as it'll be automatically included during processing that specific field
			if(strpos($lineKey, 'free_') === 0) {
				continue;
			}
			if($lineVal == -1 && $lineKey == 'installation') {//-1 for installation means that this is not possible so skip it
				continue;
			}
			$freeLineVal = $priceSec->lines->{'free_'.$lineKey};
			if($lineKey == 'installation' && empty($freeLineVal)) {
				$freeLineVal = $priceSec->lines->{'free_install'};//An exception for installation as its keys doesn't match in API when free
			}
			$priceDisplayVal = $lineVal->product->display_value;
			$extraClass      = '';
			if ( !is_numeric($lineVal->product->value) ) {
				$extraClass      = 'class="prominent"';
				$priceDisplayVal = ucfirst($lineVal->product->display_value);
			}
			elseif ( $lineVal->product->value === 0 ) {
				$extraClass      = 'class="prominent"';
				$priceDisplayVal = pll__( 'Free' );
			}
			if( $lineVal->product->value <= 0 || !is_numeric($lineVal->product->value) || isset($freeLineVal) ) {
				$yearlyLineVal = $lineVal;
				if(isset($freeLineVal)) {
					$yearlyLineVal = $freeLineVal;
				}
				$yearlyAdvPrice = $yearlyLineVal->product->value*$yearlyLineVal->multiplicand->value;
				$yearlyAdvDisplayPrice = formatPrice($yearlyAdvPrice, 2, $yearlyLineVal->product->unit);
				$extraClass      = 'class="prominent"';
				if(!is_numeric($yearlyLineVal->product->value)) {
					$yearlyAdvDisplayPrice = ucfirst($yearlyLineVal->product->display_value);
				}

				$yearlyAdvCollection[] = ['label' => $yearlyLineVal->label,
				                          'price_value' => $yearlyLineVal->product->value,
				                          'price_display_value' => $priceDisplayVal,
				                          'price_multiplied_val' => $yearlyAdvPrice,
				                          'price_multiplied_display_val' => $yearlyAdvDisplayPrice];
			}

			$hasOldPrice = false;
			$hasOldPriceClass = '';
			$oldPriceHtml = '';
			$promoPriceHtml = '';
			$offerPrice = 0;
			$actualPrice = $lineVal->product->value;
			if(isset($freeLineVal)) {//its free part exist as well
				$hasOldPrice = true;
				$hasOldPriceClass = 'hasOldPrice';
				$offerPrice = $freeLinePrice = abs($freeLineVal->product->value);
				$currLinePrice = $lineVal->product->value;

				$remainingPrice = $currLinePrice - $freeLinePrice;

				$priceDisplayVal = formatPrice($remainingPrice, 2, $lineVal->product->unit);
				$freeLineDisplayPrice = formatPrice($freeLinePrice, 2, '', '', true, true);

				//$priceDisplayVal = "<span style='text-decoration: line-through'>{$freeLineDisplayPrice}</span> $priceDisplayVal";
				$oldPriceHtml = '<span class="oldPrice">'.$freeLineVal->product->unit.' '.$freeLineDisplayPrice.'</span>';

				$promoPriceHtml = $this->generatePbsPromoHtml( $freeLineDisplayPrice, $freeLineVal );
			}

			$priceDetailHtml = $this->generatePbsPackOptionHtml( $lineVal, $oldPriceHtml, $hasOldPriceClass, $promoPriceHtml, $offerPrice );

			$htmlArr[] = $priceDetailHtml;

			if($lineKey == 'discount_fee_amount_extra') {//if this exist then move it to 2nd place
				$tmpHtml = $htmlArr[1];//storing 2nd index value in temp variable
				$extraClass      = 'class="prominent"';
				$mulVal = $lineVal->product->value*$lineVal->multiplicand->value;
				$mulValDisplay = formatPrice($mulVal, 2, $lineVal->product->unit);
				//$htmlArr[1] = '<li ' . $extraClass . '>' . $mulValDisplay . ' ' . $lineVal->label . '</li>';
				$htmlArr[1] = $this->generatePbsPackOptionHtml( $lineVal, '', '', $this->generatePbsPromoHtml( $mulValDisplay, $lineVal), $offerPrice );
				$htmlArr[count($htmlArr)-1] = $tmpHtml;//Now brining value stored in 2
			}
			if ( $productCount > 0 ) {
				//This means that its a child product you can add remove symbol here.
			}
		}

		$html .= implode( '', $htmlArr );

		return $html;
	}

	/**
	 * @param $lineVal
	 * @param $oldPriceHtml
	 * @param $hasOldPriceClass
	 * @param $promoPriceHtml
	 *
	 * @return string
	 */
	private function generatePbsPackOptionHtml( $lineVal, $oldPriceHtml, $hasOldPriceClass, $promoPriceHtml, $offerPrice ) {
		$priceText = '';
		if(!is_numeric($lineVal->product->value)) {
			$priceText = ucfirst($lineVal->product->value);
		}
		$priceArr = formatPriceInParts( $lineVal->product->value - $offerPrice, 2, $lineVal->product->unit );

		if($offerPrice == 0) {
			$oldPriceHtml = '';
		}

		$currPriceHtml = '<span class="currentPrice">
					                <span class="currency">' . $priceArr['currency'] . '</span>
					                <span class="amount">' . $priceArr['price'] . '</span>
					                <span class="cents">' . $priceArr['cents'] . '</span>
					            </span>';

		if($priceText) {
			$currPriceHtml = '<span class="currentPrice">'.$priceText.'</span>';
		}

		$currOldPriceHtml = '<div class="packagePrice">
				            ' . $oldPriceHtml . $currPriceHtml . '
				            </div>';

		$priceDetailHtml = '<li class="packOption">
								<div class="packageDetail">
								<div class="packageDesc ' . $hasOldPriceClass . '">' . $lineVal->label . '</div>
					            ' . $currOldPriceHtml . $promoPriceHtml . '
					            </div>
					            </li>';

		return $priceDetailHtml;
	}

	/**
	 * @param $freeLineDisplayPrice
	 * @param $freeLineVal
	 *
	 * @return string
	 */
	private function generatePbsPromoHtml( $freeLineDisplayPrice, $freeLineVal ) {
		$promoPriceHtml = '<div class="packagePromo">
					                <ul class="list-unstyled">
					                    <li class="promo prominent">' . $freeLineDisplayPrice . ' ' . $freeLineVal->label . '</li>
					                </ul>
					            </div>';

		return $promoPriceHtml;
	}

	/**
	 * @param $total
	 * @param $priceSec
	 *
	 * @return string
	 */
	private function generatePbsSectionTotalHtml( $total, $priceSec, $sectionTotalLabel, $infoTextLabel = '', $infoTextHelpText = '' ) {
		$sectionTotalPriceArr = formatPriceInParts( $total, 2, $priceSec->subtotal->unit );
		$infoTextHtml = '';

		if(empty($sectionTotalLabel)) {
			$sectionTotalLabel = $priceSec->label;
		}

		if($infoTextHelpText) {
			$infoTextHtml = '<div class="additionalInfo">
                                <p>' . $infoTextLabel . ' <a href="#" class="tip" data-toggle="tooltip" title="<p>' . $infoTextHelpText . '</p>">?</a></p>
                            </div>';
		}
		$sectionTotalHtml     = '<div class="calcPanelTotal">
			                            <div class="packageTotal">
			                                <span class="caption">' . $sectionTotalLabel . '</span>
			                                <span class="price">
                                                <span class="currency">' . $sectionTotalPriceArr['currency'] . '</span>
                                                <span class="amount">' . $sectionTotalPriceArr['price'] . '</span>
                                                <span class="cents">' . $sectionTotalPriceArr['cents'] . '</span>
                                            </span>
			                            </div>
			                            <!-- optional additonal Info -->
			                            '.$infoTextHtml.'
			                            <!-- optional additonal Info -->
			                        </div>';

		return $sectionTotalHtml;
	}

	/**
	 * @param $existingHtml
	 * @param $priceSec
	 * @param $productCount
	 * @param $total
	 * @param $yearlyAdvCollection
	 * @param string $infoTextLabel
	 * @param string $infoText
	 *
	 * @return array
	 */
	private function generatePbsSectionHtml( $existingHtml, $pbsSectionClass, $priceSec, $productCount, $total, &$yearlyAdvCollection,
		&$sectionsHtml, $sectionTitle = '', $sectionTotalLabel='', $infoTextLabel='', $infoText='' ) {
		if(empty($sectionTitle)) {
			$sectionTitle = $priceSec->label;
		}
		$html = '<div class="calcSection '.$pbsSectionClass.'">';
		$html .= '<div class="calcPanelHeader">';
		$html .= '<h6>' . $sectionTitle . '</h6>';
		$html .= '<i class="aan-icon panelOpen fa fa-chevron-down"></i>
                            	 <i class="aan-icon panelClose fa fa-chevron-right"></i>';
		$html .= '</div>';
		$html .= '<div class="calcPanelOptions">
                    <ul class="list-unstyled">';
		$itemsHtml = '';

		//adjust this new HTML to existing html if some already exists like monthly, that should get generated once
		if(preg_match('/<div class="calcSection '.$pbsSectionClass.'">/', $existingHtml)) {
			$d = new \DOMDocument();
			$d->loadHTML('<?xml encoding="utf-8" ?>' . $existingHtml);//UTF8 encoding is required to keep the data clean

			$xpath = new \DOMXPath($d);
			$nodes = $xpath->query('//div[contains(@class, "'.$pbsSectionClass.'")]');//searching for the section
			$nodeDic = [];//ensure duplicates are never added
			foreach($nodes as $node) {
				$existingItems = $xpath->query('descendant::li[contains(@class, "packOption")]', $node);//searching for actual items
				foreach($existingItems as $item) {
					$nodeHash = crc32($item->textContent);
					if(!$nodeDic[$nodeHash]) {
						$itemsHtml .= $item->ownerDocument->saveHTML($item);
						$nodeDic[$nodeHash] = true;
					}
				}
			}
		}
		$itemsHtml .= $this->getPbsOrganizedHtmlApiPriceSection( $priceSec, $productCount, $yearlyAdvCollection );

		$html .= $itemsHtml;
		$html .= '</ul></div>';//end of price section

		$html .= $this->generatePbsSectionTotalHtml( $total, $priceSec, $sectionTotalLabel, $infoTextLabel, $infoText );
		$html .= '</div>';

		$sectionsHtml[$pbsSectionClass] = $html;

		return array( $html, $yearlyAdvCollection, $infoTextLabel, $infoText );
	}

	/**
	 * @param $yearlyAdvCollection
	 */
	private function generatePbsYearlyBreakdownHtml( $yearlyAdvCollection, $currency ) {
		$totalAdv = 0;
		$html     = '<div class="calcSection blue">
                        <!--heading-->
                        <div class="calcPanelHeader">
                            <h6>' . pll__( 'Year profit' ) . '</h6>
                            <i class="aan-icon panelOpen fa fa-chevron-down"></i>
                            <i class="aan-icon panelClose fa fa-chevron-right"></i>
                        </div>
                        <!--heading-->
                        <!--options-->
                        <div class="calcPanelOptions">
                            <ul class="list-unstyled">';

		foreach ( $yearlyAdvCollection as $adv ) {
			if($adv['price_multiplied_val'] == 0) {
				continue;
			}

			$totalAdv += $adv['price_multiplied_val'];
			$priceArr = formatPriceInParts( $adv['price_multiplied_val'], 2, $currency );
			$negativeSign = '';
			if($priceArr['price'] > 0) {
				$negativeSign = '- ';
			}
			$html .= '<li class="packOption">
                        <div class="packageDetail no-padding">
                            <div class="packageDesc">' . $adv['label'] . '</div>
                            <div class="packagePrice">
                                <span class="currentPrice">
                                    <span class="currency">' . $negativeSign.$priceArr['currency'] . '</span>
                                    <span class="amount">' . abs($priceArr['price']) . '</span>
                                    <span class="cents">' . $priceArr['cents'] . '</span>
                                </span>
                            </div>
                        </div>
                    </li>';
		}
		$advArr   = formatPriceInParts( $totalAdv, 2, $currency );
		$html     .= '</ul>
                        </div>
                        <!--options-->

                        <!--total for section-->
                        <div class="calcPanelTotal">
                            <div class="packageTotal">
                                <span class="caption">' . pll__( 'Your advantage' ) . '</span>
                                <span class="price">
                                    <span class="currency">' . $advArr['currency'] . '</span>
                                    <span class="amount">' . abs($advArr['price']) . '</span>
                                    <span class="cents">' . $advArr['cents'] . '</span>
                                </span>
                            </div>
                        </div>
                        <!--total for section-->
                    </div>';

		return [$html, $totalAdv];
	}

	/**
	 * @param $advPrice
	 *
	 * @return string
	 */
	public function getTotalAdvHtml( $advPrice ) {
		$advPriceArr = formatPriceInParts($advPrice, 2);
		$advHtml = '<div class="calcPanelTotal blue">
                            <div class="packageTotal">
                                <span class="caption">' . pll__( 'Your advantage' ) . '</span>
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