{sviat_backorder_lead brand_id=$product->brand_id stock=$product->variant->stock assign=sviat_blt}
<link itemprop="availability" href="https://schema.org/{if $sviat_blt}BackOrder{else}OutOfStock{/if}" />
{* Пара до availability_date у фіді: Merchant Center зіставляє її з Offer.availabilityStarts. *}
{if $sviat_blt}<meta itemprop="availabilityStarts" content="{$sviat_blt.availability_starts|escape}" />{/if}
