<form class="yform silvercart-voucher-cart-action-form" $FormAttributes >
      $CustomHtmlFormMetadata

      <fieldset>
        <legend><% _t('SilvercartVoucher.REDEEM_VOUCHER') %></legend>

        $CustomHtmlFormFieldByName(SilvercartVoucherCode)

    </fieldset>

    <div class="actionRow">
        <div class="type-button">
            <% loop Actions %>
                $Field
            <% end_loop %>
        </div>
    </div>

</form>
