<form class="yform" $FormAttributes >

      $CustomHtmlFormMetadata
      $CustomHtmlErrormessages

      <fieldset>
        <legend>Gutschein einlösen</legend>

        $CustomHtmlFormFieldByName(VoucherCode)

    </fieldset>

    <div class="actionRow">
        <div class="type-button">
            <% control Actions %>
                $Field
            <% end_control %>
        </div>
    </div>

</form>
