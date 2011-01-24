<?php
/**
 * Manages the history of a voucher.
 *
 * @package SilvercartVouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 20.01.2011
 * @license none
 */
class SilvercartVoucherHistory extends DataObject {

    /**
     * Attributes.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 24.01.2011
     */
    public static $db = array(
        'action'            => "Enum('redeemed,removed,activated,deactivated','redeemed')"
    );

    /**
     * Has-one relationships.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 24.01.2011
     */
    public static $has_one = array(
        'Customer'          => 'Member',
        'Voucher'           => 'SilvercartVoucher',
        'ShoppingCart'      => 'ShoppingCart'
    );

    /**
     * Summary fields for DataObjectManager.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 24.01.2011
     */
    public static $summary_fields = array(
        'Customer.FirstName',
        'Customer.Surname',
        'Voucher.code',
        'Created'
    );

    /**
     * Adds a history entry for a voucher.
     *
     * @param SilvercartVoucher $voucher
     * @param Member            $customer
     * @param string            $action
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 24.01.2011
     */
    public function add(SilvercartVoucher $voucher, Member $customer, $action) {
        $this->CustomerID           = $customer->ID;
        $this->VoucherID            = $voucher->ID;
        $this->ShoppingCartID       = $customer->shoppingCart()->ID;
        $this->action               = $action;
        $this->write();

        $voucher->VoucherHistory()->add($this);
    }
}