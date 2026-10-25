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

<form method="post">
    <input type="hidden" name="session_id" value="{$smarty.session.id}">

    <div class="boxed fn_toggle_wrap">
        <div class="heading_box">{$btr->sviat__backorder_lead_time__default|escape}</div>
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="heading_label">{$btr->sviat__backorder_lead_time__from|escape}</div>
                <input class="form-control{if $lt_default.error} error{/if}" type="number" min="1" max="365" name="default_from" value="{$lt_default.from|escape}">
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="heading_label">{$btr->sviat__backorder_lead_time__to|escape}</div>
                <input class="form-control{if $lt_default.error} error{/if}" type="number" min="1" max="365" name="default_to" value="{$lt_default.to|escape}">
            </div>
        </div>
        {if $lt_default.error}<div class="text-danger">{$btr->sviat__backorder_lead_time__row_error|escape}</div>{/if}
        <p>{$btr->sviat__backorder_lead_time__default_hint|escape}</p>
    </div>

    <div class="boxed fn_toggle_wrap">
        <div class="heading_box">{$btr->sviat__backorder_lead_time__brands|escape}</div>
        <p>{$btr->sviat__backorder_lead_time__brands_hint|escape}</p>
        {foreach $lt_brands as $brand}
            {$row = $lt_rows[$brand->id]}
            <div class="row mb-1">
                <div class="col-md-6 col-sm-12">
                    <div class="heading_label">{$brand->name|escape}</div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <input class="form-control{if $row.error} error{/if}" type="number" min="1" max="365" name="brands[{$brand->id|escape}][from]" value="{$row.from|escape}" placeholder="{$btr->sviat__backorder_lead_time__from|escape}">
                </div>
                <div class="col-md-3 col-sm-6">
                    <input class="form-control{if $row.error} error{/if}" type="number" min="1" max="365" name="brands[{$brand->id|escape}][to]" value="{$row.to|escape}" placeholder="{$btr->sviat__backorder_lead_time__to|escape}">
                </div>
                {if $row.error}
                    <div class="col-md-12 text-danger">{$btr->sviat__backorder_lead_time__row_error|escape}</div>
                {/if}
            </div>
        {/foreach}
    </div>

    <div class="row">
        <div class="col-lg-12 col-md-12">
            <button type="submit" class="btn btn_small btn_blue float-md-right">{$btr->general_apply|escape}</button>
        </div>
    </div>
</form>
