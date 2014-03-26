<?php
/**
 * Copyright 2011 pixeltricks GmbH
 *
 * This file is part of SilverCart.
 *
 * SilverCart is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SilverCart is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with SilverCart.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Basic voucher class.
 *
 * @package Silvercart
 * @subpackage Vouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 20.01.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
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
        'code'                      => 'Varchar(50)',
        'isActive'                  => 'Boolean',
        'minimumShoppingCartValue'  => 'Money',
        'maximumShoppingCartValue'  => 'Money',
        'quantity'                  => 'Int',
        'quantityRedeemed'          => 'Int',
        'ProductNumber'             => 'Varchar(50)',
        'RestrictValueToProduct'    => 'Boolean(0)',
    );

    /**
     * 1:1 relations
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 04.02.2011
     */
    public static $has_one = array(
        'SilvercartTax' => 'SilvercartTax'
    );

    /**
     * Casted fields
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 11.05.2011
     */
    public static $casting = array(
        'castedFormattedCreationDate'   => 'VarChar(10)'
    );

    /**
     * Many-many Relationships.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 20.01.2011
     */
    public static $many_many = array(
        'RestrictToMember'                     => 'Member',
        'RestrictToGroup'                      => 'Group',
        'RestrictToSilvercartProductGroupPage' => 'SilvercartProductGroupPage',
        'RestrictToSilvercartProduct'          => 'SilvercartProduct',
        'SilvercartVoucherHistory'             => 'SilvercartVoucherHistory'
    );

    /**
     * Belongs-many-many Relationships.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 24.01.2011
     */
    public static $belongs_many_many = array(
        'Members' => 'Member'
    );

    /**
     * A list of already checked shopping cart amounts
     *
     * @var type
     */
    protected $isShoppingCartAmountValid = array();

    /**
     * A list of already checked shopping cart positions
     *
     * @var type
     */
    protected $isValidForShoppingCartItems = array();

    /**
     * Field labels for display in tables.
     *
     * @param boolean $includerelations A boolean value to indicate if the labels returned include relation fields
     *
     * @return array
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 25.03.2014
     */
    public function fieldLabels($includerelations = true) {
        return array_merge(
            parent::fieldLabels($includerelations),
            array(
                'code'                                  => _t('SilvercartVoucher.CODE'),
                'isActive'                              => _t('SilvercartVoucher.ISACTIVE'),
                'minimumShoppingCartValue'              => _t('SilvercartVoucher.MINIMUM_SHOPPINGCART_VALUE'),
                'maximumShoppingCartValue'              => _t('SilvercartVoucher.MAXIMUM_SHOPPINGCART_VALUE'),
                'quantity'                              => _t('SilvercartVoucher.QUANTITY'),
                'quantityRedeemed'                      => _t('SilvercartVoucher.QUANTITY_REDEEMED'),
                'SilvercartTax'                         => _t('SilvercartTax.SINGULARNAME'),
                'RestrictToMember'                      => _t('SilvercartVoucher.RESTRICT_TO_MEMBER'),
                'RestrictToGroup'                       => _t('SilvercartVoucher.RESTRICT_TO_GROUP'),
                'RestrictToSilvercartProduct'           => _t('SilvercartVoucher.RESTRICT_TO_PRODUCT'),
                'RestrictToSilvercartProductGroupPage'  => _t('SilvercartVoucher.RESTRICT_TO_PRODUCTGROUP'),
                'RestrictValueToProduct'                => _t('SilvercartVoucher.RestrictValueToProduct'),
                'SilvercartVoucherHistory'              => _t('SilvercartVoucherHistory.SINGULARNAME'),
                'castedFormattedCreationDate'           => _t('SilvercartVoucher.CREATED'),
                'ProductNumber'                         => _t('SilvercartVoucher.PRODUCTNUMBER'),537
            )
        );
    }

    /**
     * Returns the summary fields for table overviews.
     *
     * @return array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>, Sebastian Diel <sdiel@pixeltricks.de>
     * @since 28.08.2012
     */
    public function summaryFields() {
        return array(
            'castedFormattedCreationDate'           => $this->fieldLabel('castedFormattedCreationDate'),
            'code'                                  => $this->fieldLabel('code'),
            'isActive'                              => $this->fieldLabel('isActive'),
            'quantity'                              => $this->fieldLabel('quantity'),
            'quantityRedeemed'                      => $this->fieldLabel('quantityRedeemed'),
        );
    }

    /**
     * Returns the searchable fields.
     *
     * @return array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>, Sebastian Diel <sdiel@pixeltricks.de>
     * @since 28.08.2012
     */
    public function searchableFields() {
        $fields = array();

        $fields['code'] = array(
            'title'     => $this->fieldLabel('code'),
            'filter'    => 'PartialMatchFilter'
        );
        $fields['quantity'] = array(
            'title'     => $this->fieldLabel('quantity'),
            'filter'    => 'PartialMatchFilter'
        );
        $fields['isActive'] = array(
            'title'     => $this->fieldLabel('isActive'),
            'filter'    => 'ExactMatchFilter'
        );

        return $fields;
    }

    // ------------------------------------------------------------------------
    // Methods
    // ------------------------------------------------------------------------

    /**
     * Returns a nicely formatted date that respects the local settings.
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 11.05.2011
     */
    public function castedFormattedCreationDate() {
        $old_locale = setlocale(LC_TIME, null);
        $new_locale = setlocale(LC_TIME, i18n::get_locale(), i18n::get_locale().'.utf8');

        $date = strftime("%x %X", strtotime($this->Created));

        setlocale(LC_TIME, $old_locale);

        return $date;
    }

    /**
     * Initialisation
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 21.01.2011
     */
    public function init() {
        parent::init();

        $member = Member::currentUser();

        if ($member) {
            $member->SilvercartShoppingCart()->registerModule($this);
        }
    }

    /**
     * Performs all checks to make sure, that this voucher is allowed in the
     * shopping cart. Returns an array with status and messages.
     *
     * @param SilvercartVoucher $voucher                the vouchers code
     * @param Member            $member                 the member object to check against
     * @param ShoppingCart      $silvercartShoppingCart the shopping cart to check against
     *
     * @return array:
     *  'error'     => bool,
     *  'messages'  => array()
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>, Patrick Schneider <pschneider@pixeltricks.de>
     * @since 06.12.2012
     */
    public function checkifAllowedInShoppingCart(SilvercartVoucher $voucher, Member $member, SilvercartShoppingCart $silvercartShoppingCart) {
        $status     = $this->areShoppingCartConditionsMet($silvercartShoppingCart);
        $error      = $status['error'];
        $messages   = $status['messages'];
        
        $voucherCode = $voucher->code;
        $voucherID   = $voucher->ID;

        if (!$error && !$this->isCodeValid($voucherCode)) {
            $error      = true;
            $messages[] = _t('SilvercartVoucher.ERRORMESSAGE-CODE_NOT_VALID', 'This voucher code is not valid.');
        }

        if (!$error && !$this->isCustomerEligible($member)) {
            $error      = true;
            $messages[] = _t('SilvercartVoucher.ERRORMESSAGE-CUSTOMER_NOT_ELIGIBLE', 'You\'re not entitled to redeem this voucher.');
        }

        if (!$error && !$this->isRedeemable()) {
            $error      = true;
            $messages[] = _t('SilvercartVoucher.ERRORMESSAGE-NOT_REDEEMABLE', 'This voucher can\'t be redeemed.');
        }
        
        if (!$error && $this->isCompletelyRedeemedAlready($member, $voucherID)) {
            $error = true;
            $messages[] = _t('SilvercartVoucher.ERRORMESSAGE-COMPLETELY_REDEEMED_ALREADY', 'This voucher is completely redeemed.');
        }
        
        if (!$error && $this->isInShoppingCartAlready($silvercartShoppingCart)) {
            $error      = true;
            $messages[] = _t('SilvercartVoucher.ERRORMESSAGE-ALREADY_IN_SHOPPINGCART', 'This voucher is already in your shoppingcart.');
        }

        return array(
            'error'     => $error,
            'messages'  => $messages
        );
    }

    /**
     * This method gets called when converting the shoppingcart positions to
     * order positions.
     * Implement it in your own voucher types if needed.
     *
     * @param SilvercartShoppingCart                $silvercartShoppingCart the shoppingcart object
     * @param SilvercartVoucherShoppingCartPosition $shoppingCartPosition   shoppingcart position with voucher
     * @param SilvercartVoucher                     $originalVoucher        the original voucher
     * @param Member                                $member                 member object
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>, Patrick Schneider <pschneider@pixeltricks.de>
     * @since 06.12.2012
     *
     */
    public function convert(SilvercartShoppingCart $silvercartShoppingCart, SilvercartVoucherShoppingCartPosition $shoppingCartPosition, SilvercartVoucher $originalVoucher, Member $member) {
        // Implement in descendants
    }

    /**
     * Performs checks related to the shopping cart entries to ensure that
     * the voucher is allowed to be placed in the cart.
     * If the conditions are not met the voucher is removed from the cart.
     *
     * @param ShoppingCart $silvercartShoppingCart       the shopping cart to check against
     * @param Member       $member                       the shopping cart to check against
     * @param array        $excludeShoppingCartPositions Positions that shall not be counted
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>, Sebastian Diel <sdiel@pixeltricks.de>
     * @since 27.11.2012
     */
    public function performShoppingCartConditionsCheck(SilvercartShoppingCart $silvercartShoppingCart, $member, $excludeShoppingCartPositions = false) {
        if ($this->ID > 0) {
            $status = $this->areShoppingCartConditionsMet($silvercartShoppingCart);

            if ($excludeShoppingCartPositions &&
                in_array($this->ID, $excludeShoppingCartPositions)) {

                return true;
            }

            if ($status['error']) {
                $silvercartVoucherShoppingCartPosition = DataObject::get_one(
                    'SilvercartVoucherShoppingCartPosition',
                    sprintf(
                        "SilvercartShoppingCartID = %d AND SilvercartVoucherID = %d",
                        $silvercartShoppingCart->ID,
                        $this->ID
                    )
                );

                if ($silvercartVoucherShoppingCartPosition &&
                    $silvercartVoucherShoppingCartPosition->implicatePosition) {
                    $silvercartVoucherShoppingCartPosition->setImplicationStatus(false);

                    $voucherHistory = new SilvercartVoucherHistory();
                    $voucherHistory->add($this, $member, 'removed');
                }
            } else {
                $silvercartVoucherShoppingCartPosition = silvercartVoucherShoppingCartPosition::get($silvercartShoppingCart->ID, $this->ID);

                if ($silvercartVoucherShoppingCartPosition &&
                    $silvercartVoucherShoppingCartPosition->implicatePosition == false) {

                    $voucherHistory = new SilvercartVoucherHistory();
                    $voucherHistory->add($this, $member, 'redeemed');

                    $silvercartVoucherShoppingCartPosition->setImplicationStatus(true);

                    $member->SilvercartVouchers()->add($this);
                }
            }
        }
        
        return true;
    }

    /**
     * Performs checks related to the shopping cart entries to ensure that
     * the voucher is allowed to be placed in the cart.
     *
     * @param ShoppingCart $silvercartShoppingCart the shopping cart to check against
     *
     * @return array:
     *  'error'     => bool,
     *  'messages'  => array()
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 24.01.2011
     */
    public function areShoppingCartConditionsMet(SilvercartShoppingCart $silvercartShoppingCart) {
        $error      = false;
        $messages   = array();

        if (!$error && !$this->isShoppingCartAmountValid($silvercartShoppingCart->getTaxableAmountGrossWithoutFeesAndCharges(array('SilvercartVoucher')))) {
            $error      = true;
            $messages[] = _t('SilvercartVoucher.ERRORMESSAGE-SHOPPINGCARTVALUE_NOT_VALID', 'The shoppingcart value is not valid.');
        }

        if (!$error && !$this->isValidForShoppingCartItems($silvercartShoppingCart->SilvercartShoppingcartPositions())) {
            $error      = true;
            $messages[] = _t('SilvercartVoucher.ERRORMESSAGE-SHOPPINGCARTITEMS_NOT_VALID', 'Your cart doesn\'t contain the appropriate products for this voucher.');
        }

        return array(
            'error'     => $error,
            'messages'  => $messages
        );
    }

    /**
     * Checks if the given code is valid by comparing it to the code in the
     * database.
     *
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

        if ($this->code === $code) {
            $isValid = true;
        }

        return $isValid;
    }

    /**
     * Checks if the given voucher code is already in the shopping cart.
     *
     * @param ShoppingCart $silvercartShoppingCart the shopping cart object
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 24.01.2011
     */
    public function isInShoppingCartAlready(SilvercartShoppingCart $silvercartShoppingCart) {
        $isInCart = false;

        if (SilvercartVoucherShoppingCartPosition::combinationExists($silvercartShoppingCart->ID, $this->ID)) {
            $isInCart = true;
        }

        return $isInCart;
    }

    /**
     * Checks if the customer is eligible to redeem the voucher by making sure
     * that he/she is not excluded by the RestrictTo-relations.
     *
     * @param Member $member the customer object
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function isCustomerEligible(Member $member) {
        $isEligibleByUndefinedMembership        = false;
        $isEligibleByMembership                 = false;
        $isEligibleByUndefinedGroupMembership   = false;
        $isEligibleByGroupMembership            = false;

        // check if voucher is restricted to single members and if so, if
        // customer is one of those members.
        if ($this->RestrictToMember()->Count() > 0) {
            if ($this->RestrictToMember()->find('ID', $member->ID)) {
                $isEligibleByMembership = true;
            }
        } else {
            // no restriction on membership level
            $isEligibleByUndefinedMembership = true;
        }

        // check if voucher is restricted to groups and if so, if customer is
        // in allowed groups
        if ($this->RestrictToGroup()->Count() > 0) {

            if ($member->ClassName == 'AnonymousCustomer') {
                $memberGroups = DataObject::get('Group', sprintf("Code LIKE '%s'", 'anonymous'));
            } else {
                $memberGroups = $member->Groups();
            }

            if ($this->findDataObjectSetInSetByKey($this->RestrictToGroup(), $memberGroups, 'ID')) {
                $isEligibleByGroupMembership = true;
            }
        } else {
            // no restriction on group membership level
            $isEligibleByUndefinedGroupMembership = true;
        }

        // --------------------------------------------------------------------
        // check if user has a permission for this voucher
        // --------------------------------------------------------------------
        if ($isEligibleByMembership &&
            $isEligibleByGroupMembership) {

            return true;
        }

        // exceptional case: no membership levels configured
        if ($isEligibleByUndefinedMembership &&
            $isEligibleByUndefinedGroupMembership) {

            return true;
        }

        // exceptional case: user is not in allowed groups, but has a
        // permission on membership level
        if (!$isEligibleByGroupMembership &&
             $isEligibleByMembership) {

            return true;
        }

        // exceptional case: user is not allowed by membership, but has a
        // permission on group membership level
        if (!$isEligibleByMembership &&
             $isEligibleByGroupMembership) {

            return true;
        }

        return false;
    }
    
    /**
     * can be used to return if a voucher is already fully redeemd,
     * set error message in checkifAllowedInShoppingCart()
     * 
     * @param Member $member    the member object
     * @param String $voucherID id of the voucher
     * 
     * @return boolean
     * 
     * @author Patrick Schneider <pschneider@pixeltricks.de>
     * @since 06.12.2012
     */
    protected function isCompletelyRedeemedAlready(Member $member, String $voucherID) {
        // Implement in descendants if needed
        return false;
    }

    /**
     * Checks if the shoppingcart total amount is within the boundaries of
     * this voucher if they are defined.
     *
     * @param Money $amount the amount of the shoppingcart.
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>, Sebastian Diel <sdiel@pixeltricks.de>
     * @since 27.11.2012
     */
    public function isShoppingCartAmountValid(Money $amount) {
        $cacheKey = (string) $amount->getAmount();
        if (!array_key_exists($cacheKey, $this->isShoppingCartAmountValid)) {
            $isShoppingCartAmountValid  = false;
            $isMinimumValid             = false;
            $isUndefinedMinimumValid    = false;
            $isMaximumValid             = false;
            $isUndefinedMaximumValid    = false;

            if ($this->minimumShoppingCartValue->getAmount() > 0) {
                if ($amount->getAmount() >= $this->minimumShoppingCartValue->getAmount()) {
                    $isMinimumValid = true;
                }
            } else {
                $isUndefinedMinimumValid = true;
            }

            if ($this->maximumShoppingCartValue->getAmount() > 0) {
                if ($amount->getAmount() <= $this->maximumShoppingCartValue->getAmount()) {
                    $isMaximumValid = true;
                }
            } else {
                $isUndefinedMaximumValid = true;
            }

            if (($isMinimumValid &&
                 $isMaximumValid) ||
                ($isUndefinedMinimumValid &&
                 $isUndefinedMaximumValid) ||
                ($isUndefinedMinimumValid &&
                 $isMaximumValid) ||
                ($isUndefinedMaximumValid &&
                $isMinimumValid)) {
                $isShoppingCartAmountValid = true;
            }
            $this->isShoppingCartAmountValid[$cacheKey] = $isShoppingCartAmountValid;
        }

        return $this->isShoppingCartAmountValid[$cacheKey];
    }

    /**
     * Checks if there are restrictions for this voucher in regars to the
     * items in the shopping cart.
     *
     * @param ShoppingCartPosition $silvercartShoppingCartPositions the shoppingcartposition object
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>, Sebastian Diel <sdiel@pixeltricks.de>
     * @since 27.11.2012
     */
    public function isValidForShoppingCartItems(SilvercartShoppingCartPosition $silvercartShoppingCartPositions) {
        $cacheKey = (string) implode('_', $silvercartShoppingCartPositions->map('ID', 'ID'));
        if (!array_key_exists($cacheKey, $this->isValidForShoppingCartItems)) {
            $isValidForShoppingCartItems    = false;
            $isValidByUndefinedProduct      = false;
            $isValidByProduct               = false;
            $isValidByUndefinedProductGroup = false;
            $isValidByProductGroup          = false;

            if ($this->RestrictToSilvercartProduct()->Count() > 0) {
                foreach ($this->RestrictToSilvercartProduct() as $restrictedProduct) {
                    foreach ($silvercartShoppingCartPositions as $silvercartShoppingCartPosition) {
                        if ($silvercartShoppingCartPosition->SilvercartProduct()->ID == $restrictedProduct->ID) {
                            $isValidByProduct = true;
                            break(2);
                        }
                    }
                }
            } else {
                $isValidByUndefinedProduct = true;
            }

            if ($this->RestrictToSilvercartProductGroupPage()->Count() > 0) {
                foreach ($this->RestrictToSilvercartProductGroupPage() as $restrictedProductGroup) {
                    foreach ($silvercartShoppingCartPositions as $silvercartShoppingCartPosition) {
                        if ($silvercartShoppingCartPosition->SilvercartProduct()->SilvercartProductGroup()->ID == $restrictedProductGroup->ID) {
                            $isValidByProductGroup = true;
                            break(2);
                        }
                    }
                }
            } else {
                $isValidByUndefinedProductGroup = true;
            }

            // --------------------------------------------------------------------
            // check if product is valid for this cart
            // --------------------------------------------------------------------
            if (($isValidByProduct &&
                 $isValidByProductGroup) ||
                // exceptional case: no product and groups defined
                ($isValidByUndefinedProduct &&
                 $isValidByUndefinedProductGroup) ||
                (!$isValidByProductGroup &&
                 $isValidByProduct) ||
                (!$isValidByProduct &&
                 $isValidByProductGroup)) {
                $isValidForShoppingCartItems = true;
            }

            $this->isValidForShoppingCartItems[$cacheKey] = $isValidForShoppingCartItems;
        }
        return $this->isValidForShoppingCartItems[$cacheKey];
    }

    /**
     * Returns the number of remaining vouchers.
     *
     * @return int
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function getRemainingVouchers() {
        $remainingVouchers = 0;

        if ($this->quantity !== null) {
            $remainingVouchers = $this->quantity - $this->quantityRedeemed;
        }

        return $remainingVouchers;
    }

    /**
     * Checks if the voucher is active and if there are enough remaining
     * vouchers if the quantity is restricted.
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function isRedeemable() {
        $isRedeemable = false;

        if ($this->isActive) {
            if ($this->quantity == -1 ||
                $this->getRemainingVouchers() > 0) {

                $isRedeemable = true;
            }
        }

        return $isRedeemable;
    }

    /**
     * Redeem the voucher.
     *
     * @param Member $member the customer object
     * @param string $action the action for commenting
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function redeem(Member $member, $action = 'redeemed') {
        // Write SilvercartVoucherHistory
        $voucherHistory = new SilvercartVoucherHistory();
        $voucherHistory->add($this, $member, $action);

        // Connect voucher with shopping cart
        SilvercartVoucherShoppingCartPosition::add($member->SilvercartShoppingCart()->ID, $this->ID);
    }

    /**
     * Remove the voucher from the shopping cart.
     *
     * @param Member $member the customer object
     * @param string $action the action for commenting
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>, Patrick Schneider <pschneider@pixeltricks.de>
     * @since 06.12.2012
     *
     */
    public function removeFromShoppingCart(Member $member, $action = 'removed') {
        $voucherHistory = new SilvercartVoucherHistory();
        $voucherHistory->add($this, $member, $action);

        // Disconnect voucher from shopping cart
        SilvercartVoucherShoppingCartPosition::remove($member->SilvercartShoppingCart()->ID, $this->ID);
    }

    /**
     * Returns an instance of a silvercart voucher object for the given
     * shopping cart.
     *
     * @param SilvercartShoppingcart $silvercartShoppingCart The shopping cart object
     *
     * @return SilvercartVoucher
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 24.01.2011
     */
    public function loadObjectForShoppingCart(SilvercartShoppingCart $silvercartShoppingCart) {
        $voucherHistory = $this->getLastHistoryEntry($silvercartShoppingCart);

        if ($voucherHistory) {
            $voucher = DataObject::get_by_id(
                'SilvercartVoucher',
                $voucherHistory->SilvercartVoucherObjectID
            );

            if ($voucher) {
                return $voucher;
            }
        }
        
        return $this;
    }

    /**
     * This method is a hook that gets called by the shoppingcart.
     *
     * It returns an entry for the cart listing.
     *
     * @param ShoppingCart $silvercartShoppingCart       The shoppingcart object
     * @param Member       $member                       The customer object
     * @param Bool         $taxable                      Indicates if taxable or nontaxable entries should be returned
     * @param array        $excludeShoppingCartPositions Positions that shall not be counted; can contain the ID or the className of the position
     * @param Bool         $createForms                  Indicates wether the form objects should be created or not
     *
     * @return DataObjectSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 21.01.2011
     */
    public function ShoppingCartPositions(SilvercartShoppingCart $silvercartShoppingCart, Member $member, $taxable = true, $excludeShoppingCartPositions = false, $createForms = true) {
        $positions = array();
        $records   = DB::query(
            sprintf(
                "
                SELECT DISTINCT
                    VHis.SilvercartVoucherObjectID
                FROM
                    SilvercartVoucherHistory VHis
                WHERE
                    VHis.SilvercartShoppingCartID = %d
                ORDER BY
                    VHis.LastEdited DESC
                ",
                $silvercartShoppingCart->ID
            )
        );
        
        foreach ($records as $record) {
            $voucher = DataObject::get_by_id('SilvercartVoucher', $record['SilvercartVoucherObjectID']);

            if ($voucher) {
                $silvercartVoucherShoppingCartPosition = SilvercartVoucherShoppingCartPosition::get($silvercartShoppingCart->ID, $voucher->ID);

                if ($silvercartVoucherShoppingCartPosition &&
                    $silvercartVoucherShoppingCartPosition->implicatePosition) {

                    $silvercartShoppingCartPositions = $voucher->getSilvercartShoppingCartPositions($silvercartShoppingCart, $taxable, $excludeShoppingCartPositions, $createForms);

                    if ($silvercartShoppingCartPositions) {
                        foreach ($silvercartShoppingCartPositions as $key => $silvercartShoppingCartPosition) {
                            $positions[] = $silvercartShoppingCartPosition;
                        }
                    }
                }
            }
        }
        
        return new DataObjectSet($positions);
    }

    /**
     * This method is a hook that gets called by the shoppingcart.
     *
     * It disconnects the voucher from the shopping cart after they are converted to order positions
     *
     * @param ShoppingCart $silvercartShoppingCart The shoppingcart object
     * @param Member       $member                 The customer object
     * @param Bool         $taxable                Indicates if taxable or nontaxable entries should be returned
     *
     * @return DataObjectSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>, Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.08.2012
     */
    public function ShoppingCartConvert(SilvercartShoppingCart $silvercartShoppingCart, Member $member, $taxable = true) {
        $shoppingCartPositions  = DataObject::get(
            'SilvercartVoucherShoppingCartPosition',
            sprintf(
                "SilvercartShoppingCartID = %d",
                $silvercartShoppingCart->ID
            )
        );        

        if ($shoppingCartPositions) {
            foreach ($shoppingCartPositions as $shoppingCartPosition) {
                $originalVoucher = DataObject::get_by_id('SilvercartVoucher', $shoppingCartPosition->SilvercartVoucherID, false);

                if ($originalVoucher) {
                    // Adjust quantity
                    if ($originalVoucher->quantity > 0) {
                        $originalVoucher->quantity -= 1;
                    }

                    if (!$member->SilvercartVouchers()->find('ID', $originalVoucher->ID)) {
                        // increase redeemd quantity only if no relation exists
                        $originalVoucher->quantityRedeemed += 1;
                    }

                    // Call conversion method on every voucher
                    if (method_exists($shoppingCartPosition->SilvercartVoucher(), 'convert')) {
                        $shoppingCartPosition->SilvercartVoucher()->convert($silvercartShoppingCart, $shoppingCartPosition, $originalVoucher, $member);
                    }

                    // save changes to original voucher
                    $originalVoucher->write();

                    // And remove from the customers shopping cart
                    SilvercartVoucherShoppingCartPosition::remove($silvercartShoppingCart->ID, $shoppingCartPosition->SilvercartVoucherID);
                }
            }
        }
    }

    /**
     * This method is a hook that gets called by the shoppingcart.
     *
     * It returns taxable entries for the cart listing.
     *
     * @param SilvercartShoppingCart $silvercartShoppingCart The Silvercart shoppingcart object
     * @param Member                 $member                 The member object
     *
     * @return DataObjectSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 04.02.2011
     */
    public function TaxableShoppingCartPositions(SilvercartShoppingCart $silvercartShoppingCart, Member $member) {
        $positions = $this->ShoppingCartPositions($silvercartShoppingCart, $member, true);

        return $positions;
    }

    /**
     * This method is a hook that gets called by the shoppingcart.
     *
     * It returns nontaxable entries for the cart listing.
     *
     * @param SilvercartShoppingCart $silvercartShoppingCart The Silvercart shoppingcart object
     * @param Member                 $member                 The member object
     *
     * @return DataObjectSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 04.02.2011
     */
    public function NonTaxableShoppingCartPositions(SilvercartShoppingCart $silvercartShoppingCart, Member $member) {
        $positions = $this->ShoppingCartPositions($silvercartShoppingCart, $member, false);

        return $positions;
    }

    /**
     * Return the last history entry or false if none was found for the
     * given shoppingcart object.
     *
     * @param SilvercartShoppingCart $silvercartShoppingCart the shoppingcart object
     *
     * @return mixed SilvercartVoucherHistory|bool false
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 25.01.2011
     */
    public function getLastHistoryEntry(SilvercartShoppingCart $silvercartShoppingCart) {
        $voucherHistory = DataObject::get_one(
            'SilvercartVoucherHistory',
            sprintf(
                "SilvercartShoppingCartID = '%d'",
                $silvercartShoppingCart->ID
            ),
            false,
            "Created DESC"
        );

        return $voucherHistory;
    }

    /**
     * This method is a hook that gets called by the shoppingcart.
     *
     * It returns input fields for the entry of the voucher code and insertion
     * into the shopping cart.
     *
     * @param SilvercartShoppingCart $silvercartShoppingCart the shoppingcart object
     *
     * @return DataObjectSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 21.01.2011
     */
    public function ShoppingCartActions(SilvercartShoppingCart $silvercartShoppingCart) {
        $controller = Controller::curr();

        // Don't initialise when called from within the cms
        if (!$controller->isFrontendPage) {
            return false;
        }

        $actions                        = new DataObjectSet();
        $silvercartShoppingCartActions  = $controller->getRegisteredCustomHtmlForm('SilvercartVoucherShoppingCartActionForm');

        $actions->push(
            new ArrayData(
                array(
                    'moduleOutput' => $silvercartShoppingCartActions
                )
            )
        );

        return $actions;
    }

    /**
     * Hook for the init method of the shopping cart.
     *
     * It registers the form for the voucher code that is used by
     * {$this->ShoppingCartActions}.
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 21.01.2011
     */
    public function ShoppingCartInit() {
        $controller = Controller::curr();

        // Don't initialise when called from within the cms
        if (!$controller->isFrontendPage) {
            return false;
        }

        if (!$controller->getRegisteredCustomHtmlForm('SilvercartVoucherShoppingCartActionForm')) {
            $actionForm = new SilvercartVoucherShoppingCartActionForm($controller);
            $controller->registerCustomHtmlForm(
                'SilvercartVoucherShoppingCartActionForm',
                $actionForm
            );
        }

        $member = Member::currentUser();

        if (!$member ||
             $member->SilvercartShoppingCartID === 0) {

            return false;
        }

        $records   = DB::query(
            sprintf(
                "
                SELECT DISTINCT VHis.SilvercartVoucherObjectID
                FROM
                    SilvercartVoucherHistory VHis
                WHERE
                    VHis.SilvercartShoppingCartID = %d
                ORDER BY
                    VHis.LastEdited DESC
                ",
                $member->SilvercartShoppingCartID
            )
        );

        foreach ($records as $record) {
            $voucher = DataObject::get_by_id('SilvercartVoucher', $record['SilvercartVoucherObjectID']);

            if ($voucher) {
                $removeFromCartForm = new SilvercartVoucherRemoveFromCartForm($controller, array('SilvercartVoucherID' => $voucher->ID));

                $controller->registerCustomHtmlForm(
                    'SilvercartVoucherRemoveFromCartForm'.$voucher->ID,
                    $removeFromCartForm
                );
            }
        }
    }

    /**
     * Returns the amount to consider in the shopping cart total calculation.
     *
     * @return Money
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 24.01.2011
     */
    public function ShoppingCartTotal() {
        $member = Member::currentUser();

        if (!$member ||
             $member->SilvercartShoppingCartID === 0) {

            return false;
        }

        $amountObj = new Money();
        $amount    = 0;

        $records   = DB::query(
            sprintf(
                "
                SELECT DISTINCT
                    VHis.SilvercartVoucherObjectID
                FROM
                    SilvercartVoucherHistory VHis
                WHERE
                    VHis.SilvercartShoppingCartID = %d
                ORDER BY
                    VHis.LastEdited DESC
                ",
                $member->SilvercartShoppingCartID
            )
        );

        foreach ($records as $record) {
            $voucher = DataObject::get_by_id('SilvercartVoucher', $record['SilvercartVoucherObjectID']);

            if ($voucher) {
                $amount += $voucher->getSilvercartShoppingCartTotal()->getAmount();
            }
        }

        $amountObj->setAmount($amount);

        return $amountObj;
    }

    /**
     * Define the backend administration masks.
     *
     * @param array $params Additional parameters
     *
     * @return FieldSet
     */
    public function  getCMSFields($params = null) {
        $fieldClasses = array(
                'minimumShoppingCartValue'  => 'SilvercartMoneyField',
                'maximumShoppingCartValue'  => 'SilvercartMoneyField',
        );
        if (is_array($params) &&
            array_key_exists('fieldClasses', $params)) {
            $params['fieldClasses'] = array_merge(
                    $params['fieldClasses'],
                    $fieldClasses
            );
        } else {
            if (!is_array($params)) {
                $params = array();
            }
            $params['fieldClasses'] = $fieldClasses;
        }
        $fields = parent::getCMSFields($params);

        $memberTableField = new ManyManyComplexTableField(
            $this,
            'RestrictToMember',
            'Member',
            null,
            'getCMSFields_forPopup',
            'Member.Surname IS NOT NULL',
            'Member.Surname ASC, Member.FirstName ASC'
        );
        $groupTableField = new ManyManyComplexTableField(
            $this,
            'RestrictToGroup',
            'Group',
            null,
            'getCMSFields_forPopup',
            null,
            'Group.Title ASC'
        );
        $productTableField = new ManyManyComplexTableField(
            $this,
            'RestrictToSilvercartProduct',
            'SilvercartProduct',
            null,
            'getCMSFields_forPopup',
            null
        );
        $productGroupHolder    = SilvercartTools::PageByIdentifierCode('SilvercartProductGroupHolder');
        $productGroupPageField = new TreeMultiselectField(
                'RestrictToSilvercartProductGroupPage',
                $this->fieldLabel('RestrictToSilvercartProductGroupPage'),
                'SiteTree'
        );
        $productGroupPageField->setTreeBaseID($productGroupHolder->ID);

        
        $restrictValueToProductCopy = clone $fields->dataFieldByName('RestrictValueToProduct');
        $restrictValueToProductCopy->setName('RestrictValueToProduct_Copy');
        $restrictValueToProductCopy->setValue($this->RestrictValueToProduct);
        Requirements::javascript('silvercart_marketing_vouchers/js/RestrictValueToProduct.js');

        $fields->removeByName('RestrictToMember');
        $fields->removeByName('RestrictToGroup');
        $fields->removeByName('RestrictToSilvercartProduct');
        $fields->removeByName('RestrictToSilvercartProductGroupPage');

        $fields->findOrMakeTab('Root.RestrictToMember',                         _t('SilvercartVoucher.RESTRICT_TO_MEMBER'));
        $fields->addFieldToTab('Root.RestrictToMember',                         $memberTableField);
        $fields->findOrMakeTab('Root.RestrictToGroup',                          _t('SilvercartVoucher.RESTRICT_TO_GROUP'));
        $fields->addFieldToTab('Root.RestrictToGroup',                          $groupTableField);
        $fields->findOrMakeTab('Root.RestrictToSilvercartProduct',              _t('SilvercartVoucher.RESTRICT_TO_PRODUCT'));
        $fields->addFieldToTab('Root.RestrictToSilvercartProduct',              $fields->dataFieldByName('RestrictValueToProduct'));
        $fields->addFieldToTab('Root.RestrictToSilvercartProduct',              $productTableField);
        $fields->findOrMakeTab('Root.RestrictToSilvercartProductGroupPage',     _t('SilvercartVoucher.RESTRICT_TO_PRODUCTGROUP'));
        $fields->addFieldToTab('Root.RestrictToSilvercartProductGroupPage',     $restrictValueToProductCopy);
        $fields->addFieldToTab('Root.RestrictToSilvercartProductGroupPage',     $productGroupPageField);
        
        if ($this->ClassName == 'SilvercartAbsoluteRebateVoucher') {
            $fields->removeByName('RestrictValueToProduct');
        }

        return $fields;
    }

    /**
     * Checks if a tax rate is attributed to this voucher. If not, we try
     * to get a 0% rate.
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 04.02.2011
     */
    public function onAfterWrite() {
        parent::onAfterWrite();

        if (!$this->SilvercartTaxID) {
            $taxRateZero = DataObject::get_one(
                'SilvercartTax',
                "Rate = 0"
            );

            if ($taxRateZero) {
                $this->SilvercartTaxID = $taxRateZero->ID;
                $this->write();
            }
        }
    }

    /**
     * Returns a dataobjectset for the display of the voucher positions in the
     * shoppingcart.
     *
     * @param SilvercartShoppingCart $silvercartShoppingCart the shoppingcart object
     *
     * @return bool false
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 24.01.2011
     */
    protected function getSilvercartShoppingCartPositions(SilvercartShoppingCart $silvercartShoppingCart) {
        // Implement in descendants
        return false;
    }

    /**
     * Check if a value of a key of a DataObjectSet is contained in another
     * DataObjectSet.
     *
     * @param DataObjectSet $set1 the first set to search in
     * @param DataObjectSet $set2 the second set to search in
     * @param string        $key  the key to search for
     *
     * @return boolean
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 21.01.2011
     */
    private function findDataObjectSetInSetByKey(DataObjectSet $set1, DataObjectSet $set2, $key) {
        $foundKey = false;

        foreach ($set2 as $iteratorSet) {
            if ($set1->find($key, $iteratorSet->$key)) {
                $foundKey = true;
                break;
            }
        }

        return $foundKey;
    }
    
    /**
     * Returns a voucher with the given code.
     * 
     * @param string $code Voucher code
     * 
     * @return SilvercartVoucher
     */
    public static function get_by_code($code) {
        $voucher = DataObject::get_one(
            'SilvercartVoucher',
            sprintf(
                "code = '%s'",
                $code
            )
        );
        return $voucher;
    }

    /**
     * Generates a single voucher code.
     * 
     * @return string
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 02.12.2013
     */
    public static function generate_code() {
        $code       = '';
        $codeParts  = 4;
        $partLength = 5;
        
        for ($i = 0; $i < $codeParts; $i++) {
            for ($j = 0; $j < $partLength; $j++) {
                $code .= strtoupper(dechex(rand(0,15)));
            }
            $code .= '-';
        }
        
        $code = substr($code, 0, strlen($code) - 1);
        
        if (SilvercartVoucher::get_by_code($code) instanceof SilvercartVoucher) {
            $code = self::generate_code();
        }
        
        return $code;
    }
    
    /**
     * Generates voucher codes.
     * 
     * @param int $count Count of voucher codes to generate
     * 
     * @return array
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 02.12.2013
     */
    public static function generate_codes($count = 1) {
        $codes = array();
        for ($x = 0; $x < $count; $x++) {
            $codes[] = self::generate_code();
        }
        return $codes;
    }
}
