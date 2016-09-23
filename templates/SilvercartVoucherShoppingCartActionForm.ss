<form class="yform silvercart-voucher-cart-action-form" {$FormAttributes} >
      {$CustomHtmlFormMetadata}
      <fieldset>
        <legend><% _t('SilvercartVoucher.REDEEM_VOUCHER') %></legend>
        $CustomHtmlFormFieldByName(SilvercartVoucherCode, CustomHtmlFormFieldWithSubmitButton)
    </fieldset>
</form>