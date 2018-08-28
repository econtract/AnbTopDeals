/**
 * Created by Israr on 08/16/2018.
 */
jQuery(document).ready(function($){

    $( ".dealsTable .custom-deals" ).each(function( index ) {
        var get = $.grep(this.className.split(" "), function(v, i){
            return v.indexOf('slider') === 0;
        }).join();
        console.log(get);

        ///LANDING PAGE REVIEW SLIDER IN MOBILE
        if(jQuery('.' + get).length>0){
            jQuery('.' + get).owlCarousel({
                loop: false,
                margin: 0,
                nav: false,
                dot: true,
                responsive: {
                    0: {
                        items: 1,
                        nav: false,
                        dots: true,
                        margin: 0,
                        loop:false
                    },
                    480: {
                        items: 1,
                        nav: false,
                        dots: true,
                        margin: 0,
                        loop:false
                    },
                    767: {
                        items: 2,
                        dots: true,
                        margin: 0,
                        loop:false
                    },
                    1000: {
                        items: 3,
                    }
                }

            });
        }

    });
    fixDealsTableHeight($('.dealsTable'));
});