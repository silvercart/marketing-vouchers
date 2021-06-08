<% if $IncludeFormTag %>
<form {$addErrorClass('was-validated').AttributesHTML}>
<% end_if %>
<% include SilverCart/Forms/CustomFormMessages %>
<% loop $HiddenFields %>
    {$Field}
<% end_loop %>
    {$BeforeFormContent}
    <label for="{$Fields.dataFieldByName('VoucherCode').HTMLID}">{$Fields.dataFieldByName('VoucherCode').Title}</label>
    <div class="input-group">
        <div class="input-group-prepend d-none d-xxl-flex">
            <span class="input-group-text"><span class="fa fa-qrcode"></span></span>
        </div>
        {$Fields.dataFieldByName('VoucherCode').addErrorClass('is-invalid').Field}
        <div class="input-group-append">
    <% loop $Actions %>
        <button class="btn btn-outline-primary" id="{$ID}" title="{$Title}" name="{$Name}" type="submit"><span class="fa fa-check-circle"></span> {$Title}</button>
    <% end_loop %>
        </div>
    </div>
    {$CustomFormSpecialFields}
    {$AfterFormContent}
<% if $IncludeFormTag %>
</form>
<% end_if %>