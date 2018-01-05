/**
 * Created by imran on 6/21/17.
 */

function appendToSelector(selector, html) {
    jQuery(selector).append(html).promise().done(function () {
        if(selector === ".topDeals .dealsTable"){
            fixDealsTableHeight(jQuery('.dealsTable.topDealsTable'));
        }
    });
}

// function appendToSelector(selector, htmlJSON) {
//     var htmlObj = JSON.parse(htmlJSON);
//     $(selector).append(htmlObj.html);
// }

function appendNav(selector, html) {
    jQuery(selector).append(html);
}

jQuery(document).ready(function($){
    $('.topDeals .filterDeals ul li a').on('click', function() {
        $('.topDeals .filterDeals ul li.active').removeClass('active');//remove previous active class

        //hide all rows now
        $('.topDeals .dealsTable .row').hide();

        //show the clicked one
        $(this).parents('li').addClass('active');
        $('.row.'+$(this).attr('related')).show();
    });
});

/**

 var myvar = '<div class="col-md-4 offer left">'+
'                <div class="dealDetails">'+
'                    <div class="bestReviewBadge">'+
'                        <span>BEST</span>'+
'                        <span class="bold">Review</span>'+
'                    </div>'+
'                    <div class="customerRating">'+
'                        <div class="stamp">'+
'                            8.4'+
'                        </div>'+
'                    </div>'+
'                    <div class="dealLogo">'+
'                        <img src="<?php bloginfo(\'template_url\') ?>/images/common/providers/proximus.png" alt="Proximus Tuttimus">'+
'                    </div>'+
'                    <h4>Proximus Tuttimus</h4>'+
'                    <p class="slogan">De eerste all-in voor je gezin</p>'+
'                    <div class="services">'+
'                        <ul class="list-unstyled list-inline">'+
'                            <li>'+
'                                <i class="fa fa-wifi"></i>'+
'                            </li>'+
'                            <li>'+
'                                <i class="fa fa-mobile"></i>'+
'                            </li>'+
'                            <li>'+
'                                <i class="fa fa-phone"></i>'+
'                            </li>'+
'                            <li>'+
'                                <i class="fa fa-tv"></i>'+
'                            </li>'+
'                        </ul>'+
'                    </div>'+
'                </div>'+
'                <div class="dealPrice">'+
'                    <div class="oldPrice">'+
'                        <span class="amount">110</span><span class="cents">95</span>'+
'                    </div>'+
'                    <div class="newPrice">'+
'                        <span class="amount">97<span class="cents">95</span><span class="recursion">/mth</span></span>'+
'                    </div>'+
'                    <div class="priceInfo">'+
'                        <ul class="list-unstyled">'+
'                            <li>the first 6 months</li>'+
'                            <li>? 1200 the first year'+
'                                <span class="calc">'+
'                                    <a href="#"><i class="fa fa-calculator"></a></i>'+
'                                </span>'+
'                            </li>'+
'                        </ul>'+
'                    </div>'+
'                </div>'+
'                <div class="dealFeatures">'+
'                    <div class="extras">'+
'                        <ul class="list-unstyled">'+
'                            <li>Installation 65?</li>'+
'                            <li>Activation 50?</li>'+
'                        </ul>'+
'                    </div>'+
'                    <div class="advantages">'+
'                        <p> </p>'+
'                    </div>'+
'                </div>'+
'                <a href="#" class="btn btn-primary">Info and options</a>'+
'             </div>';


 **/