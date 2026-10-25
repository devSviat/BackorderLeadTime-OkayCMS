{sviat_backorder_lead brand_id=$product->brand_id assign=sviat_blt_any}
{* Блок лише для товару, де є варіант під замовлення; між варіантами його перемикає JS теми за класом fn_is_preorder. *}
{$sviat_blt_has_zero = false}
{foreach $product->variants as $sviat_blt_v}{if $sviat_blt_v->stock == 0}{$sviat_blt_has_zero = true}{/if}{/foreach}
{if $sviat_blt_any && $sviat_blt_has_zero}
{$sviat_blt_days = $sviat_blt_any.to|plural:$lang->sviat__backorder_lead_time__day_1:$lang->sviat__backorder_lead_time__day_5:$lang->sviat__backorder_lead_time__day_2}
<div class="fn_is_preorder sviat_backorder_lead sviat_backorder_lead--card{if $product->variant->stock > 0} hidden-xs-up{/if}">
    <svg class="sviat_backorder_lead__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
    <div>
        <div class="sviat_backorder_lead__term">{if $sviat_blt_any.from == $sviat_blt_any.to}{$lang->sviat__backorder_lead_time__ships_in_exact|sprintf:$sviat_blt_any.to:$sviat_blt_days|escape}{else}{$lang->sviat__backorder_lead_time__ships_in|sprintf:$sviat_blt_any.from:$sviat_blt_any.to:$sviat_blt_days|escape}{/if}</div>
        <div class="sviat_backorder_lead__date">{$lang->sviat__backorder_lead_time__estimated_by|sprintf:$sviat_blt_any.date_label|escape}</div>
    </div>
</div>
{/if}
