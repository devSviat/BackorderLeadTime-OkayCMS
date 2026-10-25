{sviat_backorder_lead brand_id=$purchase->product->brand_id stock=$purchase->variant->stock assign=sviat_blt}
{if $sviat_blt}
<span class="sviat_backorder_lead"> · {$sviat_blt.from}–{$sviat_blt.to} {$sviat_blt.to|plural:$lang->sviat__backorder_lead_time__day_1:$lang->sviat__backorder_lead_time__day_5:$lang->sviat__backorder_lead_time__day_2|escape}</span>
{/if}
