{* Закриває capture, відкритий перед рядком наявності картки (module.json). Текст «немає» стоїть і в мітці (сирий), і в data-out (escape), з якого тема відновлює мітку при зміні варіанта. *}
{/capture}
{sviat_backorder_lead brand_id=$product->brand_id assign=sviat_blt_card_lead}
{if $sviat_blt_card_lead}
{$sviat_blt_card_label = $lang->sviat__backorder_lead_time__label|escape}
{$sviat_blt_card_out = $lang->out_of_stock|escape}
{$sviat_blt_card|replace:$lang->out_of_stock:$sviat_blt_card_label|replace:$sviat_blt_card_out:$sviat_blt_card_label}
{else}
{$sviat_blt_card}
{/if}
