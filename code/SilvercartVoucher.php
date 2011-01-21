<?php
/**
 * Basic voucher class.
 *
 * @package SilvercartVouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 20.01.2011
 * @license none
 */
class SilvercartVoucher extends DataObject {

    /**
     * Attributes.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 20.01.2011
     */
    public static $db = array(
        'code'                      => 'Varchar(20)',
        'minimumShoppingCartValue'  => 'Money',
        'maximumShoppingCartValue'  => 'Money',
        'quantity'                  => 'Int',
        'quantityRedeemed'          => 'Int'
    );

    /**
     * Many-many Relationships.
     *
     * @var array
     *
     * @TODO insert RestrictToCategory Relationship when class is available
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 20.01.2011
     */
    public static $many_many = array(
        'RestrictToMember'              => 'Member',
        'RestrictToGroup'               => 'Group',
        'RestrictToArticleGroupPage'    => 'ArticleGroupPage',
        'RestrictToArticle'             => 'Article',
        'VoucherHistory'                => 'VoucherHistory'
    );

    // ------------------------------------------------------------------------
    // Methods
    // ------------------------------------------------------------------------

    /**
     * @param string $code the voucher code
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function isCodeValid($code) {
        $isValid = false;

        return $isValid;
    }

    /**
     * @param int $customerId the customer ID
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function isCustomerEligible($customerId) {
        $isEligible = false;

        return $isEligible;
    }

    /**
     * @param Money $amount the amount of the shoppingcart.
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function isShoppingCartAmountValid(Money $amount) {
        $isValid = false;

        return $isValid;
    }

    /**
     * @param ShoppingCart $shoppingCart the shoppingcart object
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function isValidForShoppingCartItems(ShoppingCart $shoppingCart) {
        $isValid = false;

        return $isValid;
    }

    /**
     * @return int
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function getRemainingVouchers() {
        $remainingVouchers = false;

        return $remainingVouchers;
    }

    /**
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function isRedeemable() {
        $isRedeemable = false;

        return $isRedeemable;
    }

    /**
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function setRedeemed() {
    }
}