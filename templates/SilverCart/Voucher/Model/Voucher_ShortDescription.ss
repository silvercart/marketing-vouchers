<% if $isLimitedToRestrictedProducts %>
    <% if $AffectedShoppingCartPositions.count == 1 %>
    <span class="text-blue-dark-85"><%t SilverCart\Voucher.LimitedToRestrictedProductsSingular 'This voucher is valid for the product <i><u>{title}</u></i>.' title=$AffectedShoppingCartPositions.first.Title %></span>
    <% else_if $AffectedShoppingCartPositions.count > 1 %>
    <div class="d-inline-block text-blue-dark-85 text-left">
        <%t SilverCart\Voucher.LimitedToRestrictedProductsPlural 'This voucher is valid for the products:' %><br/>
        <ul class="mb-0">
        <% loop $AffectedShoppingCartPositions %>
            <li>{$Title}</li>
        <% end_loop %>
        </ul>
    </div>
    <% end_if %>
<% end_if %>
