{sviat_backorder_lead brand_id=$product->brand_id stock=$product->variant->stock assign=sviat_blt}
<link itemprop="availability" href="https://schema.org/{if $sviat_blt}BackOrder{else}OutOfStock{/if}" />
