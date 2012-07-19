<form class="yform" $FormAttributes >
      $CustomHtmlFormMetadata

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
