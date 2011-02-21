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
        'isActive'                  => 'Boolean',
        'minimumShoppingCartValue'  => 'Money',
        'maximumShoppingCartValue'  => 'Money',
        'quantity'                  => 'Int',
        'quantityRedeemed'          => 'Int'
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
        'RestrictToMember'                     => 'Member',
        'RestrictToGroup'                      => 'Group',
        'RestrictToSilvercartProductGroupPage' => 'SilvercartProductGroupPage',
        'RestrictToSilvercartProduct'          => 'SilvercartProduct',
        'VoucherHistory'                       => 'SilvercartVoucherHistory'
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

    // ------------------------------------------------------------------------
    // Methods
    // ------------------------------------------------------------------------

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
     * @param string       $voucherCode  the vouchers code
     * @param Member       $member       the member object to check against
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
    public function checkifAllowedInShoppingCart($voucherCode, Member $member, SilvercartShoppingCart $silvercartShoppingCart) {
        $status     = $this->areShoppingCartConditionsMet($silvercartShoppingCart);
        $error      = $status['error'];
        $messages   = $status['messages'];
        
        if (!$error && !$this->isCodeValid($voucherCode)) {
            $error      = true;
            $messages[] = _t('ERRORMESSAGE-CODE_NOT_VALID', 'Dieser Gutscheincode ist nicht gültig.');
        }

        if (!$error && !$this->isCustomerEligible($member)) {
            $error      = true;
            $messages[] = _t('ERRORMESSAGE-CUSTOMER_NOT_ELIGIBLE', 'Sie dürfen diesen Gutschein nicht einlösen.');
        }

        if (!$error && !$this->isRedeemable()) {
            $error      = true;
            $messages[] = _t('ERRORMESSAGE-NOT_REDEEMABLE', 'Der Gutschein kann nicht eingelöst werden.');
        }

        if (!$error && $this->isInShoppingCartAlready($silvercartShoppingCart)) {
            $error      = true;
            $messages[] = _t('ERRORMESSAGE-ALREADY_IN_SHOPPINGCART', 'Dieser Gutschein befindet sich schon in Ihrem Warenkorb.');
        }

        return array(
            'error'     => $error,
            'messages'  => $messages
        );
    }

    /**
     * Performs checks related to the shopping cart entries to ensure that
     * the voucher is allowed to be placed in the cart.
     * If the conditions are not met the voucher is removed from the cart.
     *
     * @param ShoppingCart $silvercartShoppingCart the shopping cart to check against
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 24.01.2011
     */
    public function performShoppingCartConditionsCheck(SilvercartShoppingCart $silvercartShoppingCart, Member $member) {
        $status = $this->areShoppingCartConditionsMet($silvercartShoppingCart);

        if ($status['error']) {
            $silvercartVoucherShoppingCartPosition = SilvercartVoucherShoppingCartPosition::get($silvercartShoppingCart->ID, $this->ID);

            if ($silvercartVoucherShoppingCartPosition) {
                $silvercartVoucherShoppingCartPosition->setImplicationStatus(false);
            }
        } else {
            $silvercartVoucherShoppingCartPosition = SilvercartVoucherShoppingCartPosition::get($silvercartShoppingCart->ID, $this->ID);

            if ($silvercartVoucherShoppingCartPosition &&
                $silvercartVoucherShoppingCartPosition->implicatePosition == false) {

                $voucherHistory = new SilvercartVoucherHistory();
                $voucherHistory->add($this, $member, 'redeemed');

                $silvercartVoucherShoppingCartPosition->setImplicationStatus(true);

                $member->SilvercartVouchers()->add($this);
            }
        }
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

        if (!$error && !$this->isShoppingCartAmountValid($silvercartShoppingCart->getTaxableAmountGrossWithoutFees(array('SilvercartVoucher')))) {
            $error      = true;
            $messages[] = _t('ERRORMESSAGE-SHOPPINGCARTVALUE_NOT_VALID', 'Der Warenkorbwert ist nicht passend.');
        }

        if (!$error && !$this->isValidForShoppingCartItems($silvercartShoppingCart->SilvercartShoppingcartPositions())) {
            $error      = true;
            $messages[] = _t('ERRORMESSAGE-SHOPPINGCARTITEMS_NOT_VALID', 'Dieser Gutschein kann nicht für die Waren eingelöst werden, die sich in Ihrem Warenkorb befinden.');
        }

        return array(
            'error'     => $error,
            'messages'  => $messages
        );
    }

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
     * Checks if the shoppingcart total amount is within the boundaries of
     * this voucher if they are defined.
     *
     * @param Money $amount the amount of the shoppingcart.
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function isShoppingCartAmountValid(Money $amount) {
        $isMinimumValid          = false;
        $isUndefinedMinimumValid = false;
        $isMaximumValid          = false;
        $isUndefinedMaximumValid = false;

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

        if ($isMinimumValid &&
            $isMaximumValid) {

            return true;
        }
        if ($isUndefinedMinimumValid &&
            $isUndefinedMaximumValid) {

            return true;
        }
        if ($isUndefinedMinimumValid &&
            $isMaximumValid) {

            return true;
        }
        if ($isUndefinedMaximumValid &&
            $isMinimumValid) {

            return true;
        }

        return false;
    }

    /**
     * @param ShoppingCartPosition $silvercartShoppingCartPositions the shoppingcartposition object
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function isValidForShoppingCartItems(SilvercartShoppingCartPosition $silvercartShoppingCartPositions) {
        $isValidByUndefinedProduct      = false;
        $isValidByProduct               = false;
        $isValidByUndefinedProductGroup = false;
        $isValidByProductGroup          = false;

        if ($this->RestrictToProduct()->Count() > 0)  {
            foreach ($this->RestrictToProduct() as $restrictedProduct) {
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

        if ($this->RestrictToProductGroupPage()->Count() > 0)  {
            foreach ($this->RestrictToProductGroupPage() as $restrictedProductGroup) {
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
        if ($isValidByProduct &&
            $isValidByProductGroup) {

            return true;
        }

        // exceptional case: no product and groups defined
        if ($isValidByUndefinedProduct &&
            $isValidByUndefinedProductGroup) {

            return true;
        }

        if (!$isValidByProductGroup &&
             $isValidByProduct) {

            return true;
        }

        if (!$isValidByProduct &&
             $isValidByProductGroup) {

            return true;
        }

        return false;
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
     * @param string $action   the action for commenting
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
     * @param string $action   the action for commenting
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 24.01.2011
     *
     */
    public function removeFromShoppingCart(Member $member, $action = 'removed') {
        if ($this->quantity != -1) {
            $this->quantity += 1;
        }
        $this->write();

        $voucherHistory = new SilvercartVoucherHistory();
        $voucherHistory->add($this, $member, $action);

        $member->SilvercartVouchers()->remove($this);

        // Disconnect voucher from shopping cart
        SilvercartVoucherShoppingCartPosition::remove($member->SilvercartShoppingCart()->ID, $this->ID);
    }

    /**
     * Returns an instance of a silvercart voucher object for the given
     * shopping cart.
     *
     * @param Shoppingcart $silvercartShoppingCart The shopping cart object
     *
     * @return SilvercartVoucher
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 24.01.2011
     */
    public function loadObjectForShoppingCart(Shoppingcart $silvercartShoppingCart) {
        $voucherHistory = $this->getLastHistoryEntry($silvercartShoppingCart);

        if ($voucherHistory) {
            $voucher = DataObject::get_by_id(
                'SilvercartVoucher',
                $voucherHistory->VoucherID
            );

            if ($voucher) {
                return $voucher;
            }
        }

        return false;
    }

    /**
     * This method is a hook that gets called by the shoppingcart.
     *
     * It returns an entry for the cart listing.
     *
     * @param ShoppingCart $silvercartShoppingCart The shoppingcart object
     * @param Member       $member     The customer object
     * @param Bool         $taxable      Indicates if taxable or nontaxable entries should be returned
     *
     * @return DataObjectSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 21.01.2011
     */
    public function ShoppingCartPositions(SilvercartShoppingCart $silvercartShoppingCart, Member $member, $taxable = true) {
        $positions = array();
        $vouchers  = DataObject::get(
            'SilvercartVoucher',
            "isActive = 1"
        );

        if ($vouchers) {
            foreach ($vouchers as $voucher) {
                $voucher->performShoppingCartConditionsCheck($silvercartShoppingCart, $member);

                $silvercartVoucherShoppingCartPosition = SilvercartVoucherShoppingCartPosition::get($silvercartShoppingCart->ID, $voucher->ID);

                if ($silvercartVoucherShoppingCartPosition &&
                    $silvercartVoucherShoppingCartPosition->implicatePosition) {

                    $silvercartShoppingCartPositions = $voucher->getShoppingCartPositions($silvercartShoppingCart, $taxable);

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
     * It disconnects the voucher from the shopping cart.
     *
     * @param ShoppingCart $silvercartShoppingCart The shoppingcart object
     * @param Member       $member     The customer object
     * @param Bool         $taxable      Indicates if taxable or nontaxable entries should be returned
     *
     * @return DataObjectSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 07.02.2011
     */
    public function ShoppingCartConvert(SilvercartShoppingCart $silvercartShoppingCart, Member $member, $taxable = true) {
        $vouchers  = DataObject::get(
            'SilvercartVoucher',
            "isActive = 1"
        );

        foreach ($vouchers as $voucher) {

            // Adjust quantity
            if ($voucher->quantity > 0) {
                $voucher->quantity -= 1;
            }

            $voucher->quantityRedeemed += 1;
            $voucher->write();

            // Connect voucher to customer
            $member->SilvercartVouchers()->add($this);

            // And remove from the customers shopping cart
            SilvercartVoucherShoppingCartPosition::remove($silvercartShoppingCart->ID, $voucher->ID);
        }
    }

    /**
     * This method is a hook that gets called by the shoppingcart.
     *
     * It returns taxable entries for the cart listing.
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
     * @param Shoppingcart $silvercartShoppingCart the shoppingcart object
     *
     * @return mixed SilvercartVoucherHistory|bool false
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 25.01.2011
     */
    public function getLastHistoryEntry(Shoppingcart $silvercartShoppingCart) {
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
     * @return DataObjectSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 21.01.2011
     */
    public function ShoppingCartActions(SilvercartShoppingCart $silvercartShoppingCart) {
        $actions                = new DataObjectSet();
        $silvercartShoppingCartActions    = Controller::curr()->getRegisteredCustomHtmlForm('SilvercartVoucherShoppingCartActionForm');

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
        $controller         = Controller::curr();
        $actionForm         = new SilvercartVoucherShoppingCartActionForm($controller);

        $controller->registerCustomHtmlForm(
            'SilvercartVoucherShoppingCartActionForm',
            $actionForm
        );
        $vouchers = DataObject::get(
            'SilvercartVoucher',
            "isActive = 1"
        );

        foreach ($vouchers as $voucher) {
            $removeFromCartForm = new SilvercartVoucherRemoveFromCartForm($controller);

            $controller->registerCustomHtmlForm(
                'SilvercartVoucherRemoveFromCartForm'.$voucher->ID,
                $removeFromCartForm
            );
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
        $amountObj = new Money();
        $amount    = 0;
        $vouchers  = DataObject::get(
            'SilvercartVoucher'
        );

        if ($vouchers) {
            foreach ($vouchers as $voucher) {
                $amount += $voucher->getShoppingCartTotal()->getAmount();
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
        $fields = parent::getCMSFields($params);

        $memberTableField = new ManyManyComplexTableField(
            $this,
            'RestrictToMember',
            'Member',
            Member::$summary_fields,
            'getCMSFields_forPopup',
            'Member.Surname IS NOT NULL',
            'Member.Surname ASC, Member.FirstName ASC'
        );
        $groupTableField = new ManyManyComplexTableField(
            $this,
            'RestrictToGroup',
            'Group',
            Group::$summary_fields,
            'getCMSFields_forPopup',
            null,
            'Group.Title ASC'
        );
        $productTableField = new ManyManyComplexTableField(
            $this,
            'RestrictToProduct',
            'Product',
            Product::$summary_fields,
            'getCMSFields_forPopup',
            null,
            'Product.Title ASC'
        );
        $productGroupPageTableField = new ManyManyComplexTableField(
            $this,
            'RestrictToProductGroupPage',
            'ProductGroupPage',
            ProductGroupPage::$summary_fields,
            'getCMSFields_forPopup',
            null,
            'SiteTree.Title ASC'
        );

        $fields->removeByName('RestrictToMember');
        $fields->removeByName('RestrictToGroup');
        $fields->removeByName('RestrictToProduct');
        $fields->removeByName('RestrictToProductGroupPage');

        $fields->addFieldToTab('Root.RestrictToMember',             $memberTableField);
        $fields->addFieldToTab('Root.RestrictToGroup',              $groupTableField);
        $fields->addFieldToTab('Root.RestrictToProduct',            $productTableField);
        $fields->addFieldToTab('Root.RestrictToProductGroupPage',   $productGroupPageTableField);

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

        if (!$this->TaxID) {
            $taxRateZero = DataObject::get_one(
                'Tax',
                "Rate = 0"
            );

            if ($taxRateZero) {
                $this->TaxID = $taxRateZero->ID;
                $this->write();
            }
        }
    }

    /**
     * Returns a dataobjectset for the display of the voucher positions in the
     * shoppingcart.
     *
     * @return bool false
     *
     * @param ShoppingCart $silvercartShoppingCart
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 24.01.2011
     */
    protected function getShoppingCartPositions(SilvercartShoppingCart $silvercartShoppingCart) {
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
}