{* $sviat_blt_badge — уміст бейджа теми, перехоплений capture з module.json: у кожній темі навколо тексту своя розмітка (іконка, span), а міняється лише текст. *}
{sviat_backorder_lead brand_id=$product->brand_id assign=sviat_blt_badge_lead}
{if $sviat_blt_badge_lead}
{$sviat_blt_badge_label = $lang->sviat__backorder_lead_time__label|escape}
{$sviat_blt_badge|replace:$lang->product_out_of_stock:$sviat_blt_badge_label}
{else}
{$sviat_blt_badge}
{/if}
