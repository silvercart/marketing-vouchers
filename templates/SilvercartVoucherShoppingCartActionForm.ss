<form class="yform" $FormAttributes >
      $CustomHtmlFormMetadata
      
        <% if HasCustomHtmlFormErrorMessages %>
            <div class="silvercart-error-list">
                <div class="silvercart-error-list_content">
                    $CustomHtmlFormErrorMessages
                </div>
            </div>
        <% end_if %>

      <fieldset>
        <legend><% _t('SilvercartVoucher.REDEEM_VOUCHER') %></legend>

        $CustomHtmlFormFieldByName(SilvercartVoucherCode)

    </fieldset>

    <div class="actionRow">
        <div class="type-button">
            <% control Actions %>
                $Field
            <% end_control %>
        </div>
    </div>

</form>
