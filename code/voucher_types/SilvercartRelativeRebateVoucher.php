<?php
/**
 * Extends the voucher class for relative rebates that are subtracted from
 * the shoppingcart total sum (e.g. 10%).
 *
 * @package SilvercartVouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 20.01.2011
 * @license none
 */
class SilvercartRelativeRebateVoucher extends SilvercartVoucher {

    public static $singular_name = 'Rabattgutschein';
    public static $plural_name   = 'Rabattgutscheine';

    /**
     * Attributes.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 20.01.2011
     */
    public static $db = array(
        'valueInPercent'                    => 'Int'
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

    /**
     * Redefine input fields for the backend.
     *
     * @param array params Additional parameters
     *
     * @return FieldSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 21.01.2011
     */
    public function  getCMSFields($params = null) {
        $fields = parent::getCMSFields($params);

        return $fields;
    }
}