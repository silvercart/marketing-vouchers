<form class="yform" $FormAttributes >

      $CustomHtmlFormMetadata
      $CustomHtmlFormErrorMessages

      <fieldset>
        <legend>Gutschein einlösen</legend>

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
