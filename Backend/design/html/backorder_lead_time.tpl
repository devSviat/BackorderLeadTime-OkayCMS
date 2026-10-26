{$meta_title = $btr->sviat__backorder_lead_time__title scope=global}

<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="heading_page">{$btr->sviat__backorder_lead_time__title|escape}</div>
    </div>
</div>

{if $message_success}
    <div class="alert alert--center alert--icon alert--success">
        <div class="alert__content">
            <div class="alert__title">{$btr->general_settings_saved|escape}</div>
        </div>
    </div>
{/if}
{if $message_error}
    <div class="alert alert--center alert--icon alert--error">
        <div class="alert__content">
            <div class="alert__title">{$btr->sviat__backorder_lead_time__has_errors|escape}</div>
        </div>
    </div>
{/if}

{if $lt_default.from === null || $lt_default.from === ''}
    <div class="alert alert--icon alert--error">
        <div class="alert__content">
            <div class="alert__title">{$btr->sviat__backorder_lead_time__default_missing|escape}</div>
        </div>
    </div>
{/if}

{* Плейсхолдер порожнього рядка — загальний термін, поки той валідний. *}
{$lt_default_from = ''}{$lt_default_to = ''}
{if $lt_default.preview}{$lt_default_from = $lt_default.from}{$lt_default_to = $lt_default.to}{/if}

<form method="post" class="fn_fast_button sviat_blt" id="fn_sviat_blt"
      data-texts="{$lt_texts|escape}"
      data-today="{$lt_today|escape}"
      data-max="{$lt_max_days|escape}"
      data-by="{$btr->sviat__backorder_lead_time__preview_by|escape}"
      data-off="{$btr->sviat__backorder_lead_time__preview_off|escape}">
    <input type="hidden" name="session_id" value="{$smarty.session.id|escape}">

    <div class="boxed">
        <div class="heading_box">{$btr->sviat__backorder_lead_time__default|escape}</div>
        <div class="sviat_blt__default fn_sviat_blt_default">
            <label class="sviat_blt__field">
                <span class="heading_label">{$btr->sviat__backorder_lead_time__col_from|escape}</span>
                <span class="input-group sviat_blt__days">
                    <input class="form-control fn_sviat_blt_from{if $lt_default.error} error{/if}" type="number" min="1" max="{$lt_max_days|escape}" inputmode="numeric" name="default_from" value="{$lt_default.from|escape}">
                    <span class="input-group-addon">{$btr->sviat__backorder_lead_time__days|escape}</span>
                </span>
            </label>
            <label class="sviat_blt__field">
                <span class="heading_label">{$btr->sviat__backorder_lead_time__col_to|escape}</span>
                <span class="input-group sviat_blt__days">
                    <input class="form-control fn_sviat_blt_to{if $lt_default.error} error{/if}" type="number" min="1" max="{$lt_max_days|escape}" inputmode="numeric" name="default_to" value="{$lt_default.to|escape}">
                    <span class="input-group-addon">{$btr->sviat__backorder_lead_time__days|escape}</span>
                </span>
            </label>
        </div>
        {if $lt_default.error}<div class="text-danger">{$btr->sviat__backorder_lead_time__row_error|escape}</div>{/if}
        <p class="sviat_blt__sentence">
            {$btr->sviat__backorder_lead_time__buyer_sees|escape}
            <strong class="fn_sviat_blt_sentence">{if $lt_default.sentence}«{$lt_default.sentence|escape}»{else}—{/if}</strong>
        </p>
        <p class="sviat_blt__hint">{$btr->sviat__backorder_lead_time__default_hint|escape}</p>
    </div>

    <div class="boxed">
        <div class="heading_box">{$btr->sviat__backorder_lead_time__ads|escape}</div>
        <div class="okay_switch clearfix">
            <label class="switch_label" for="sviat_blt_exclude_ads">{$btr->sviat__backorder_lead_time__exclude_ads|escape}</label>
            <label class="switch switch-default">
                <input class="switch-input" id="sviat_blt_exclude_ads" name="exclude_from_ads" value="1" type="checkbox"{if $lt_exclude_ads} checked{/if}>
                <span class="switch-label"></span>
                <span class="switch-handle"></span>
            </label>
        </div>
        <p class="sviat_blt__hint">{$btr->sviat__backorder_lead_time__exclude_ads_hint|escape}</p>
    </div>

    <div class="boxed">
        <div class="heading_box">{$btr->sviat__backorder_lead_time__brands|escape}</div>
        <p class="sviat_blt__hint">
            {$btr->sviat__backorder_lead_time__brands_hint|escape}
            {$btr->sviat__backorder_lead_time__enabled_hint|escape}
            {$btr->sviat__backorder_lead_time__backorders_hint|escape}
        </p>
        {if !$lt_preorder}
            <p class="sviat_blt__hint text_attention">{$btr->sviat__backorder_lead_time__preorder_off|escape}</p>
        {/if}

        {$lt_count_own = 0}{$lt_count_backorders = 0}{$lt_count_off = 0}
        {foreach $lt_rows as $lt_r}{if $lt_r.own}{$lt_count_own = $lt_count_own + 1}{/if}{if $lt_r.backorders > 0}{$lt_count_backorders = $lt_count_backorders + 1}{/if}{if !$lt_r.enabled}{$lt_count_off = $lt_count_off + 1}{/if}{/foreach}
        <div class="sviat_blt__toolbar">
            <input class="form-control sviat_blt__search fn_sviat_blt_search" type="search" autocomplete="off"
                   placeholder="{$btr->sviat__backorder_lead_time__search|escape}"
                   aria-label="{$btr->sviat__backorder_lead_time__search|escape}">
            <div class="sviat_blt__filters" role="group">
                <button type="button" class="sviat_blt__filter fn_sviat_blt_filter is-active" data-filter="all" aria-pressed="true">
                    {$btr->sviat__backorder_lead_time__filter_all|escape} <span class="sviat_blt__filter_count">{$lt_rows|count}</span>
                </button>
                <button type="button" class="sviat_blt__filter fn_sviat_blt_filter" data-filter="own" aria-pressed="false">
                    {$btr->sviat__backorder_lead_time__filter_own|escape} <span class="sviat_blt__filter_count fn_sviat_blt_count_own">{$lt_count_own}</span>
                </button>
                <button type="button" class="sviat_blt__filter fn_sviat_blt_filter" data-filter="backorders" aria-pressed="false">
                    {$btr->sviat__backorder_lead_time__filter_backorders|escape} <span class="sviat_blt__filter_count">{$lt_count_backorders}</span>
                </button>
                <button type="button" class="sviat_blt__filter fn_sviat_blt_filter" data-filter="off" aria-pressed="false">
                    {$btr->sviat__backorder_lead_time__filter_off|escape} <span class="sviat_blt__filter_count fn_sviat_blt_count_off">{$lt_count_off}</span>
                </button>
            </div>
        </div>

        <table class="sviat_blt__table">
            <thead>
                <tr>
                    <th class="sviat_blt__col_brand" aria-sort="ascending">
                        <button type="button" class="sviat_blt__sort fn_sviat_blt_sort" data-sort="name">{$btr->sviat__backorder_lead_time__col_brand|escape}</button>
                    </th>
                    <th class="sviat_blt__col_count">
                        <button type="button" class="sviat_blt__sort fn_sviat_blt_sort" data-sort="backorders">{$btr->sviat__backorder_lead_time__col_backorders|escape}</button>
                    </th>
                    <th class="sviat_blt__col_on">{$btr->sviat__backorder_lead_time__col_enabled|escape}</th>
                    <th class="sviat_blt__col_days">{$btr->sviat__backorder_lead_time__col_from|escape}</th>
                    <th class="sviat_blt__col_days">{$btr->sviat__backorder_lead_time__col_to|escape}</th>
                    <th>{$btr->sviat__backorder_lead_time__col_preview|escape}</th>
                </tr>
            </thead>
            <tbody>
                {foreach $lt_brands as $brand}
                    {$row = $lt_rows[$brand->id]}
                    <tr class="sviat_blt__row fn_sviat_blt_row{if $row.error} is-error{/if}{if !$row.enabled} is-off{/if}" data-backorders="{$row.backorders|escape}" data-index="{$brand@index}">
                        <td class="sviat_blt__brand">
                            <span class="fn_sviat_blt_name">{$brand->name|escape}</span>
                            {if $row.error}<div class="text-danger sviat_blt__error">{$btr->sviat__backorder_lead_time__row_error|escape}</div>{/if}
                        </td>
                        <td class="sviat_blt__count" data-label="{$btr->sviat__backorder_lead_time__col_backorders|escape}">{$row.backorders|escape}</td>
                        <td class="sviat_blt__on" data-label="{$btr->sviat__backorder_lead_time__col_enabled|escape}">
                            <label class="switch switch-default">
                                <input class="switch-input fn_sviat_blt_enabled" type="checkbox" name="brands[{$brand->id|escape}][enabled]" value="1"{if $row.enabled} checked{/if}
                                       aria-label="{$brand->name|escape}: {$btr->sviat__backorder_lead_time__col_enabled|escape}">
                                <span class="switch-label"></span>
                                <span class="switch-handle"></span>
                            </label>
                        </td>
                        <td data-label="{$btr->sviat__backorder_lead_time__col_from|escape}">
                            <div class="input-group sviat_blt__days">
                                <input class="form-control fn_sviat_blt_from{if $row.error} error{/if}" type="number" min="1" max="{$lt_max_days|escape}" inputmode="numeric"
                                       name="brands[{$brand->id|escape}][from]" value="{$row.from|escape}" placeholder="{$lt_default_from|escape}"
                                       aria-label="{$brand->name|escape}: {$btr->sviat__backorder_lead_time__from|escape}">
                                <span class="input-group-addon">{$btr->sviat__backorder_lead_time__days|escape}</span>
                            </div>
                        </td>
                        <td data-label="{$btr->sviat__backorder_lead_time__col_to|escape}">
                            <div class="input-group sviat_blt__days">
                                <input class="form-control fn_sviat_blt_to{if $row.error} error{/if}" type="number" min="1" max="{$lt_max_days|escape}" inputmode="numeric"
                                       name="brands[{$brand->id|escape}][to]" value="{$row.to|escape}" placeholder="{$lt_default_to|escape}"
                                       aria-label="{$brand->name|escape}: {$btr->sviat__backorder_lead_time__to|escape}">
                                <span class="input-group-addon">{$btr->sviat__backorder_lead_time__days|escape}</span>
                            </div>
                        </td>
                        <td class="sviat_blt__preview" aria-live="polite">
                            <span class="fn_sviat_blt_preview">{if !$row.enabled}{$btr->sviat__backorder_lead_time__preview_off|escape}{elseif $row.preview}{$row.preview.term|escape} · {$btr->sviat__backorder_lead_time__preview_by|sprintf:$row.preview.date|escape}{else}—{/if}</span>
                            <span class="sviat_blt__general fn_sviat_blt_general"{if $row.own || !$row.preview || !$row.enabled} hidden{/if}>{$btr->sviat__backorder_lead_time__general_mark|escape}</span>
                        </td>
                    </tr>
                {/foreach}
                <tr class="fn_sviat_blt_empty" hidden>
                    <td colspan="6" class="sviat_blt__empty">{$btr->sviat__backorder_lead_time__nothing_found|escape}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="row">
        <div class="col-lg-12 col-md-12">
            <button type="submit" class="btn btn_small btn_blue float-md-right">{$btr->general_apply|escape}</button>
        </div>
    </div>
</form>
