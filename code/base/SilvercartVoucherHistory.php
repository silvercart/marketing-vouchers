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
        'action' => "Enum('redeemed,manuallyRedeemed,removed,manuallyRemoved,activated,deactivated','redeemed')"
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
        'Member'                  => 'Member',
        'SilvercartVoucherObject' => 'SilvercartVoucher',
        'SilvercartShoppingCart'  => 'SilvercartShoppingCart'
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
        'Member.FirstName',
        'Member.Surname',
        'SilvercartVoucherObject.code',
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
    public function add(SilvercartVoucher $voucher, Member $member, $action) {
        $this->MemberID= $member->ID;
        $this->SilvercartVoucherObjectID = $voucher->ID;
        $this->SilvercartShoppingCartID  = $member->SilvercartShoppingCart()->ID;
        $this->action                    = $action;
        $this->write();

        $voucher->SilvercartVoucherHistory()->add($this);
    }
}
