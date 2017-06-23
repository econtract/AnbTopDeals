# AnbTopDeals

This is how the top deals plugin can be consumed via short code. 
This snipet of the code in very first short-code is required `is_first=yes` and cannot be ignored.

```
[anb_top_deal_products product_1=packs|3041 product_2=telephony|30 product_3=internet|35 is_active=yes is_first=yes]Active Link 1[/anb_top_deal_products]

[anb_top_deal_products product_1=internet|34 product_2=telephony|30 product_3=internet|35]Inactive Link 1[/anb_top_deal_products]

[anb_top_deal_products product_1=packs|3041 product_2=telephony|30 product_3=internet|35]Inactive Link 2[/anb_top_deal_products]
```