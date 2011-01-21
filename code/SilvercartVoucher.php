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
            $member->shoppingCart()->registerModule($this);
        }
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
     * @param Member $customer the customer object
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function isCustomerEligible($customer) {
        $isEligibleByUndefinedMembership        = false;
        $isEligibleByMembership                 = false;
        $isEligibleByUndefinedGroupMembership   = false;
        $isEligibleByGroupMembership            = false;

        // check if voucher is restricted to single members and if so, if
        // customer is one of those members.
        if ($this->RestrictToMember()->Count() > 0) {
            if ($this->RestrictToMember()->find('ID', $customer->ID)) {
                $isEligibleByMembership = true;
            }
        } else {
            // no restriction on membership level
            $isEligibleByUndefinedMembership = true;
        }

        // check if voucher is restricted to groups and if so, if customer is
        // in allowed groups
        if ($this->RestrictToGroup()->Count() > 0) {

            if ($customer->ClassName == 'AnonymousCustomer') {
                $customerGroups = DataObject::get('Group', sprintf("Code LIKE '%s'", 'anonymous'));
            } else {
                $customerGroups = $customer->Groups();
            }

            if ($this->findDataObjectSetInSetByKey($this->RestrictToGroup(), $customerGroups, 'ID')) {
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
     * @param ShoppingCartPosition $shoppingCartPositions the shoppingcartposition object
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function isValidForShoppingCartItems(ShoppingCartPosition $shoppingCartPositions) {
        $isValidByUndefinedArticle      = false;
        $isValidByArticle               = false;
        $isValidByUndefinedArticleGroup = false;
        $isValidByArticleGroup          = false;

        if ($this->RestrictToArticle()->Count() > 0)  {
            foreach ($this->RestrictToArticle() as $restrictedArticle) {
                foreach ($shoppingCartPositions as $shoppingCartPosition) {
                    if ($shoppingCartPosition->article()->ID == $restrictedArticle->ID) {
                        $isValidByArticle = true;
                        break(2);
                    }
                }
            }
        } else {
            $isValidByUndefinedArticle = true;
        }

        if ($this->RestrictToArticleGroupPage()->Count() > 0)  {
            foreach ($this->RestrictToArticleGroupPage() as $restrictedArticleGroup) {
                foreach ($shoppingCartPositions as $shoppingCartPosition) {
                    if ($shoppingCartPosition->article()->articleGroup()->ID == $restrictedArticleGroup->ID) {
                        $isValidByArticleGroup = true;
                        break(2);
                    }
                }
            }
        } else {
            $isValidByUndefinedArticleGroup = true;
        }

        // --------------------------------------------------------------------
        // check if article is valid for this cart
        // --------------------------------------------------------------------
        if ($isValidByArticle &&
            $isValidByArticleGroup) {

            return true;
        }

        // exceptional case: no articles and groups defined
        if ($isValidByUndefinedArticle &&
            $isValidByUndefinedArticleGroup) {

            return true;
        }

        if (!$isValidByArticleGroup &&
             $isValidByArticle) {

            return true;
        }

        if (!$isValidByArticle &&
             $isValidByArticleGroup) {

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
            if ($this->quantity == 0 ||
                $this->getRemainingVouchers() > 0) {
                
                $isRedeemable = true;
            }
        }

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

    /**
     * This method is a hook that gets called by the shoppingcart.
     *
     * It returns an entry for the cart listing.
     *
     * @return DataObjectSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 21.01.2011
     */
    public function ShoppingCartPositions(ShoppingCart $shoppingCart) {
        $positions              = new DataObjectSet();
        $shoppingCartPosition   = '';

        $positions->push(
            new ArrayData(
                array(
                    'moduleOutput' => $shoppingCartPosition
                )
            )
        );

        return $positions;
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
    public function ShoppingCartActions(ShoppingCart $shoppingCart) {
        $actions                = new DataObjectSet();
        $shoppingCartActions    = Controller::curr()->InsertCustomHtmlForm('SilvercartVoucherShoppingCartActionForm');

        $actions->push(
            new ArrayData(
                array(
                    'moduleOutput' => $shoppingCartActions
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
        $controller             = Controller::curr();
        $actionForm             = new SilvercartVoucherShoppingCartActionForm($controller);

        $controller->registerCustomHtmlForm(
            'SilvercartVoucherShoppingCartActionForm',
            $actionForm
        );

        $javascriptSnippets = $actionForm->getJavascriptValidatorInitialisation();

        Requirements::customScript(
            $javascriptSnippets['javascriptSnippets'].
            '$(document).ready(function() {
                '.$javascriptSnippets['javascriptOnloadSnippets'].'
            });'
        );
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