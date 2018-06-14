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
                        <img src="' . $prd['logo']['200x140']->transparent->color . '" alt="' . $prd['product_name'] . '">
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

    public function getBadgeSection($prd)
    {
        // 100% needed to be extracted from $prd array
        $revSec = '101% <span>' . pll__('green') . '</span>';
        return $revSec;
    }

    public function getGreenPeaceRating($prd)
    {
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

    function getServicesHtml($product)
    {
        $servicesHtml = '';

        if ($product->producttype == 'dualfuel_pack' || strpos($product->producttype, "electricity") !== false) {
            $currProduct = ($product->electricity) ?: $product;
            $specs = $currProduct->specifications;
            $greenOriginHtml = $this->greenOriginHtmlFromSpecs($specs);
            $servicesHtml .= '<li>
	                                <span class="icons"><i class="plug-leaf"></i></span>
	                                ' . $greenOriginHtml . '
	                                <span class="desc">' . $specs->tariff_type->label . '</span>
	                                <span class="price yearly">' . formatPrice($currProduct->pricing->yearly->promo_price, 2, '&euro; ') . '</span>
	                                <span class="price monthly hide">' . formatPrice($currProduct->pricing->yearly->promo_price, 2, '&euro; ') . '</span>
	                            </li>';
        }

        if ($product->producttype == 'dualfuel_pack' || strpos($product->producttype, "gas") !== false) {
            $currProduct = ($product->gas) ?: $product;
            $specs = $currProduct->specifications;
            $greenOriginHtml = $this->greenOriginHtmlFromSpecs($specs);
            $servicesHtml .= '<li>
	                                <span class="icons"><i class="gas-leaf"></i></span>
	                                ' . $greenOriginHtml . '
	                                <span class="desc">' . $specs->tariff_type->label . '</span>
	                                <span class="price yearly">' . formatPrice($currProduct->pricing->yearly->promo_price, 2, '&euro; ') . '</span>
	                                <span class="price monthly hide">' . formatPrice($currProduct->pricing->yearly->promo_price, 2, '&euro; ') . '</span>
	                            </li>';
        }

        return $servicesHtml;
    }

    /**
     * @param $specs
     *
     * @return string
     */
    protected function greenOriginHtmlFromSpecs($specs)
    {
        $greenOrigin = $specs->green_origin;
        $greenOriginHtml = '<span class="color-green"></span>';
        if ($greenOrigin) {
            $greenOriginHtml = '<span class="color-green">' . intval($greenOrigin->value) . $greenOrigin->unit . '</span>';
        }

        return $greenOriginHtml;
    }

    public function getPromoSection($product)
    {
        $promohtml = '<div class="promo">' . pll__('promo') . '</div>';
        if (is_array($product['promotions']) && count($product['promotions']) > 0) {
            $promohtml .= '<ul class="promo-list">';
            for ($i = 0; $i < count($product['promotions']); $i++) {
                $promohtml .= '<li>' . $product['promotions'][$i] . '<span>€ 40,<small>00</small></span></li>';
            }
            $promohtml .= '</ul>';
        } else {
            $promohtml .= pll__('No promos found');
        }
        return $promohtml;
    }

    /**
     * @param array $prd
     * @param object $pricing
     * @param bool $withCalcHtml
     *
     * @return string
     */
    public function getPriceHtml($prd, $pricing, $withCalcHtml = false)
    {
        $priceHtml = '';
        $calcHtml = '';

        if ($withCalcHtml) {
            $href = "action=ajaxProductPriceBreakdownHtml&pid={$prd['product_id']}&prt={$prd['producttype']}";

            $calcHtml = '<span class="calc">
                    <a href="' . $href . '" data-toggle="modal" data-target="#calcPbsModal">
                        <i class="custom-icons calc"></i>
                    </a>
                 </span>';
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
	                            ' . $oldPriceYearlyHtml . '
	                            ' . $oldPriceMonthlyHtml . '
	                            <a href="javascript:void(0)" class="custom-icons calc" data-toggle="modal" data-target="#ratesOverview"></a>
	                        </span>
	                        <div class="current-price yearly">
	                            <span class="super">' . $promoPriceYearlyArr['currency'] . '</span>
	                            <span class="current">' . $promoPriceYearlyArr['price'] . '</span>
	                            <span class="super">,' . $promoPriceYearlyArr['cents'] . '</span>
	                            <small>' . pll__('guaranteed 1st year') . '</small>
	                        </div>
	                        <div class="current-price monthly hide">
	                            <span class="super">' . $promoPriceMonthlyArr['currency'] . '</span>
	                            <span class="current">' . $promoPriceMonthlyArr['price'] . '</span>
	                            <span class="super">,' . $promoPriceMonthlyArr['cents'] . '</span>
	                            <small>' . pll__('guaranteed 1st year') . '</small>
	                        </div>
	                    </div>';

        return $priceHtml;
    }
}