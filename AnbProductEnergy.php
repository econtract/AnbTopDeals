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

class AnbProductEnergy extends AnbProduct {

	public function __construct() {
		$this->anbApi = wpal_create_instance( Aanbieders::class, [ $this->apiConf ] );
	}

    public function getProductDetailSection( $prd, $servicesHtml = '', $includeBadge = false, $badgeTxt = '', $listView = false ) {

	    $detailsSec = '';

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
        return $detailsSec;
    }

    public function getLogoSection( array $prd ) {
        $logoSec = '<div class="dealLogo">
                        <img src="' . $prd['logo']['200x140']->transparent->color . '" alt="' . $prd['product_name'] . '">
                    </div>';
        return $logoSec;
    }

    public function getTitleSection( array $prd, $listView = false ) {
        $titleSec = '<h3>' . $prd['product_name'] . '</h3>';

        if($listView) {
            $titleSec = '<h3>' . $prd['product_name'] . '</h3>';
        }

        return $titleSec;
    }

    public function getCustomerRatingSection( $prd, $listView = false ) {
        $custRatSec = '';
        if ( (float) $prd['score'] > 0 ) {
            $custRatSec = '<div class="customer-score"><span>' . $prd['score'] . '  </span>' . pll__('Customer Score') . '</div>';

            if($listView) {
                $custRatSec = '<div class="customer-score"><span>' . $prd['score'] . '  </span>' . pll__('Customer Score') . '</div>';
            }
        }

        return $custRatSec;
    }

    public function getBadgeSection( $prd ) {
	    // 100% needed to be extracted from $prd array
        $revSec = '101% <span>' . pll__( 'green' ) . '</span>';
        return $revSec;
    }

    public function getGreenPeaceRating( $prd ) {
        $greenPeace = '<div class="greenpeace-container">
                            <div class="peace-logo"></div>
                            <fieldset>
                                <input type="radio" id="deal_1_greenPease_4" name="deal1" value="4" disabled>
                                <label class="full" for="deal_1_greenPease_4" title="4 star"></label>

                                <input type="radio" id="deal_1_greenPease_3" name="deal1" value="3" disabled>
                                <label class="full" for="deal_1_greenPease_3" title="3 star"></label>

                                <input type="radio" id="deal_1_greenPease_2" name="deal1" value="2" checked disabled>
                                <label class="full" for="deal_1_greenPease_2" title="2 star"></label>

                                <input type="radio" id="deal_1_greenPease_1" name="deal1" value="1" disabled>
                                <label class="full" for="deal_1_greenPease_1" title="1 star"></label>
                                <div class="clearfix"></div>
                            </fieldset>
                        </div>';
        return $greenPeace;
    }

	function getServicesHtml( $product ) {
		$servicesHtml = '';

		if ( strpos( $product->producttype, "electricity" ) !== false || $product->producttype == 'dualfuel_pack') {
			$specs = $product->electricity->specifications;
			$greenOriginHtml = $this->greenOriginHtmlFromSpecs( $specs );
			$servicesHtml .= '<li>
	                                <span class="icons"><i class="plug-leaf"></i></span>
	                                '.$greenOriginHtml.'
	                                <span class="desc">'.$specs->tariff_type->label.'</span>
	                                <span class="price yearly">'.formatPrice($product->electricity->pricing->yearly->promo_price, 2, '&euro; ').'</span>
	                                <span class="price monthly hide">'.formatPrice($product->electricity->pricing->yearly->promo_price, 2, '&euro; ').'</span>
	                            </li>';
		}

		if ( strpos( $product->producttype, "gas" ) !== false || $product->producttype == 'dualfuel_pack') {
			$specs = $product->gas->specifications;
			$greenOriginHtml = $this->greenOriginHtmlFromSpecs( $specs );
			$servicesHtml .= '<li>
	                                <span class="icons"><i class="gas-leaf"></i></span>
	                                '.$greenOriginHtml.'
	                                <span class="desc">'.$specs->tariff_type->label.'</span>
	                                <span class="price yearly">'.formatPrice($product->gas->pricing->yearly->promo_price, 2, '&euro; ').'</span>
	                                <span class="price monthly hide">'.formatPrice($product->gas->pricing->yearly->promo_price, 2, '&euro; ').'</span>
	                            </li>';
		}

		return $servicesHtml;
	}

	/**
	 * @param $specs
	 *
	 * @return string
	 */
	protected function greenOriginHtmlFromSpecs( $specs ): string {
		$greenOrigin     = $specs->green_origin;
		$greenOriginHtml = '<span class="color-green"></span>';
		if ( $greenOrigin ) {
			$greenOriginHtml = '<span class="color-green">' . intval( $greenOrigin->value ) . $greenOrigin->unit . '</span>';
		}

		return $greenOriginHtml;
	}
}