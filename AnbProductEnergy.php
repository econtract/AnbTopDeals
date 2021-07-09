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
use function Functional\reindex;

class AnbProductEnergy extends AnbProduct
{

    public function getGreenPeaceRating($product = null, $greenpeaceScore = null, $disabledAttr='disabled', $idPrefix = '', $returnWithoutContainer = false)
    {
        $product_id = '';
        if($product) {
            $product_id = $product->product_id;
            $greenpeaceScore = isset($product->electricity) ? $product->electricity->specifications->greenpeace_score->value : $product->specifications->greenpeace_score->value;
        } else {
            $greenpeaceScore = $greenpeaceScore ?: 0;
        }

        $greenpeaceScore = ceil($greenpeaceScore/5);

        $greenpeaceHtml = '';
        $counter = 0;
        for($i = $greenpeaceScore; $i > 0; $i--) {
            $j = $i;
            $checked = '';
            if($i == $greenpeaceScore) {
                $checked = 'checked = "checked"';
            }

            $greenpeaceHtml .= '<input type="radio" id="'.$idPrefix.'deal_'.$product_id.'_greenPease_'.$j.'" name="greenpeace'.$product_id.'" value="'.$j.'" '.$checked.' '.$disabledAttr.' greenpeace="'.$greenpeaceScore.'">
                                <label class="full" for="'.$idPrefix.'deal_'.$product_id.'_greenPease_'.$j.'" title="'.$j.' star"></label>';
            $counter++;
        }

        if($counter < 4) {
            for($i = $counter; $i < 4; $i++) {
                $j = $i+1;
                $greenpeaceHtml = '<input type="radio" id="'.$idPrefix.'deal_'.$product_id.'_greenPease_'.$j.'" name="greenpeace" value="'.$j.'" '.$disabledAttr.' greenpeace="'.$greenpeaceScore.'">
                                <label class="full" for="'.$idPrefix.'deal_'.$product_id.'_greenPease_'.$j.'" title="'.$j.' star"></label>'
                    . $greenpeaceHtml;
            }
        }

        if($returnWithoutContainer) {
            return $greenpeaceHtml;
        }

        $greenPeace = '<div class="greenpeace-container">
                            <div class="peace-logo"><img src="'.get_bloginfo('template_url').'/images/svg-icons/greenpeace-logo.svg" /></div>
                            <fieldset>
                                '.$greenpeaceHtml.'
                                <div class="clearfix"></div>
                            </fieldset>
                        </div>';
        return $greenPeace;
    }

    /**
     * @param $specs
     *
     * @return string
     */
    public function greenOriginHtmlFromSpecs($specs)
    {
        $greenOrigin = $specs->green_origin;
        $greenOriginHtml = '<span class="color-green"></span>';
        if ($greenOrigin) {
            $greenOriginHtml = '<span class="color-green">' . intval($greenOrigin->value) . $greenOrigin->unit . '</span>';
        }

        return $greenOriginHtml;
    }
}
