// TODO: Cleanup if not used (lt-restyle)
// /**
//  * Created by imran on 6/21/17.
//  */
//
// function appendToSelector(selector, html) {
//     jQuery(selector).append(html).promise().done(function () {
//         if(selector === ".topDeals .dealsTable"){
//             fixDealsTableHeight(jQuery('.dealsTable.topDealsTable'));
//         }
//     });
// }
//
// function appendNav(selector, html) {
//     jQuery(selector).append(html);
// }
//
// jQuery(document).ready(function($){
//     $('.topDeals .filterDeals ul li a').on('click', function() {
//         $('.topDeals .filterDeals ul li.active').removeClass('active');//remove previous active class
//
//         //hide all rows now
//         $('.topDeals .dealsTable .row').hide();
//
//         //show the clicked one
//         $(this).parents('li').addClass('active');
//         $('.row.'+$(this).attr('related')).show();
//     });
// });