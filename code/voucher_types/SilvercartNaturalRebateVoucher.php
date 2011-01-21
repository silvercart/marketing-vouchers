<?php
/**
 * Extends the voucher class for natural rebates, i.e. articles.
 *
 * @package SilvercartVouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 20.01.2011
 * @license none
 */
class SilvercartNaturalRebateVoucher extends SilvercartVoucher {

    public static $singular_name = 'Warengutschein';
    public static $plural_name   = 'Warengutscheine';

    /**
     * Has many relationships.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 20.01.2011
     */
    public static $has_many = array(
        'Articles'                      => 'Article'
    );

    // ------------------------------------------------------------------------
    // Methods
    // ------------------------------------------------------------------------

    /**
     * Returns a dataobjectset for the display of the voucher positions in the
     * shoppingcart.
     *
     * @param ShoppingCart $shoppingCart
     *
     * @return DataObjectSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function getShoppingCartPosition(ShoppingCart $shoppingCart) {
        $positions = new DataObjectSet;

        return $isValid;
    }
}