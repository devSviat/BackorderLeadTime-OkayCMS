{* $sviat_blt_offer читає й тема: shippingDetails Offer бере з нього handlingTime (handling_from/handling_to) для товару під замовлення. *}
{sviat_backorder_lead brand_id=$product->brand_id stock=$product->variant->stock assign=sviat_blt_offer}
<link itemprop="availability" href="https://schema.org/{if $sviat_blt_offer}BackOrder{else}OutOfStock{/if}" />
{* Пара до availability_date у фіді: Merchant Center зіставляє її з Offer.availabilityStarts. *}
{if $sviat_blt_offer}<meta itemprop="availabilityStarts" content="{$sviat_blt_offer.availability_starts|escape}" />{/if}
