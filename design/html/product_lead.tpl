{sviat_backorder_lead brand_id=$product->brand_id assign=sviat_blt_any}
{* Блок лише для товару, де є варіант під замовлення; між варіантами його перемикає JS теми за класом fn_is_preorder. *}
{$sviat_blt_has_zero = false}
{foreach $product->variants as $sviat_blt_v}{if $sviat_blt_v->stock == 0}{$sviat_blt_has_zero = true}{/if}{/foreach}
{if $sviat_blt_any && $sviat_blt_has_zero}
<div class="fn_is_preorder sviat_backorder_lead{if $product->variant->stock > 0} hidden-xs-up{/if}">
    {$sviat_blt_days = $sviat_blt_any.to|plural:$lang->sviat__backorder_lead_time__day_1:$lang->sviat__backorder_lead_time__day_5:$lang->sviat__backorder_lead_time__day_2}
    <b>{$lang->sviat__backorder_lead_time__label|escape}</b> · {$lang->sviat__backorder_lead_time__ships_in|sprintf:$sviat_blt_any.from:$sviat_blt_any.to:$sviat_blt_days:$sviat_blt_any.date_label|escape}
</div>
{/if}
