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
 * Extends the voucher class for absolute rebates, i.e. 50,00 Eur.
 *
 * @package Silvercart
 * @subpackage Vouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 20.01.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartAbsoluteRebateGiftVoucher extends SilvercartVoucher {

    /**
     * Attributes.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 20.01.2011
     */
    public static $db = array(
        'value'             => 'Money',
        'isBoundToCustomer' => 'Boolean(0)'
    );

    /**
     * Has many relationships.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 10.02.2011
     */
    public static $has_one = array(
        'SilvercartAbsoluteRebateGiftVoucherBlueprint' => 'SilvercartAbsoluteRebateGiftVoucherBlueprint',
        'Member'                                       => 'Member'
    );
    
    /**
     * Returns the translated plural name of the object. If no translation exists
     * the class name will be returned.
     * 
     * @return string
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 19.10.2011
     */
    public function plural_name() {
        if (_t('SilvercartAbsoluteRebateGiftVoucher.PLURALNAME')) {
            $plural_name = _t('SilvercartAbsoluteRebateGiftVoucher.PLURALNAME');
        } else {
            $plural_name = parent::plural_name();
        }
        return $plural_name;
    }
    
    /**
     * Returns the translated singular name of the object. If no translation exists
     * the class name will be returned.
     * 
     * @return string
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 19.10.2011
     */
    public function singular_name() {
        if (_t('SilvercartAbsoluteRebateGiftVoucher.SINGULARNAME')) {
            $singular_name = _t('SilvercartAbsoluteRebateGiftVoucher.SINGULARNAME');
        } else {
            $singular_name = parent::singular_name();
        }
        return $singular_name;
    }
    
    /**
     * Field labels for display in tables.
     *
     * @param boolean $includerelations A boolean value to indicate if the labels returned include relation fields
     *
     * @return array
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 19.10.2011
     */
    public function fieldLabels($includerelations = true) {
        return array_merge(
            parent::fieldLabels($includerelations),
            array(
                'value'                                         => _t('SilvercartAbsoluteRebateGiftVoucher.VALUE'),
                'isBoundToCustomer'                             => _t('SilvercartAbsoluteRebateGiftVoucher.IS_BOUND_TO_CUSTOMER'),
                'SilvercartAbsoluteRebateGiftVoucherBlueprint'  => _t('SilvercartAbsoluteRebateGiftVoucherBlueprint.SINGULARNAME'),
                'Member'                                        => _t('SilvercartOrder.CUSTOMER'),
            )
        );
    }

    // ------------------------------------------------------------------------
    // Methods
    // ------------------------------------------------------------------------
    
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
        // Save the member who redeems this voucher
        if ($member &&
            $member->exists()) {

            parent::redeem($member, $action);

            $this->MemberID = $member->ID;
            $this->write();
        }
    }
    
    /**
     * In order to succeed the customer must be fully registered. Otherwise
     * the binding of the voucher to the customer doesn't make sense.
     * 
     * Furthermore there mustn't be a member bound to this voucher already.
     *
     * @param string       $voucherCode            the vouchers code
     * @param Member       $member                 the member object to check against
     * @param ShoppingCart $silvercartShoppingCart the shopping cart to check against
     *
     * @return array:
     *  'error'     => bool,
     *  'messages'  => array()
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 10.05.2011
     */
    public function checkifAllowedInShoppingCart($voucherCode, Member $member, SilvercartShoppingCart $silvercartShoppingCart) {
        $status     = parent::checkifAllowedInShoppingCart($voucherCode, $member, $silvercartShoppingCart);
        $error      = $status['error'];
        $messages   = $status['messages'];

        if (!$error && (
                $this->MemberID > 0 &&
                $this->MemberID != $member->ID &&
                $this->isBoundToCustomer = true
           )) {
            $error      = true;
            $messages[] = _t('SilvercartVoucher.ERRORMESSAGE-VOUCHER_ALREADY_OWNED', 'This voucher has already been redeemed by somebody else.');
        }
        
        if (!$error && !SilvercartCustomer::currentRegisteredCustomer()) {
            $error     = true;
            $messages[] = sprintf(
                _t('SilvercartVoucher.ERRORMESSAGE-CUSTOMER_MUST_BE_REGISTERED'),
                SilvercartPage_Controller::PageByIdentifierCodeLink('SilvercartRegistrationPage').
                    '?backlink='.urlencode(Controller::curr()->Link()).
                    '&backlinkText='.urlencode(_t('SilvercartCheckoutFormStep1NewCustomerForm.CONTINUE_WITH_CHECKOUT')).
                    '&optInTempText='.urlencode(_t('SilvercartCheckoutFormStep1NewCustomerForm.OPTIN_TEMP_TEXT'))
            );
        }
        
        return array(
            'error'     => $error,
            'messages'  => $messages
        );
    }
    
    /**
     * Remove the member binding from this voucher.
     *
     * @param Member $member the customer object
     * @param string $action the action for commenting
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 24.01.2011
     *
     */
    public function removeFromShoppingCart(Member $member, $action = 'removed') {
        parent::removeFromShoppingCart($member, $action);
        
        if (!$this->isBoundToCustomer) {
            $this->MemberID = 0;
            $this->write();
        }
    }
    
    /**
     * Convert the voucher: if there is a residual value we create a new
     * absolute rebate gift voucher with this value and bind it to the
     * customer.
     * The new voucher gets the code of the original voucher, whose code in
     * turn gets renamed and gets deactivated.
     *
     * @param SilvercartShoppingCart $silvercartShoppingCart the shoppingcart object
     * @param Member                 $member                 The customer object
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 10.05.2011
     *
     */
    public function convert(SilvercartShoppingCart $silvercartShoppingCart, Member $member) {
        $originalVoucherCode = $this->code;
        
        // Set original voucher inactive
        $this->isActive = false;
        $this->write();
        
        $shoppingcartTotal = $silvercartShoppingCart->getAmountTotal(null, array($this->ID));
        
        $originalAmountObj = new Money();
        $originalAmountObj->setAmount($this->value->getAmount());
        $originalAmountObj->setCurrency($this->value->getCurrency());
        
        if ($originalAmountObj->getAmount() > $shoppingcartTotal->getAmount()) {
            
            // Get residual amount
            $restAmountObj = new Money();
            $restAmountObj->setAmount(0);
            $restAmountObj->setCurrency($originalAmountObj->getCurrency());
            $restAmountObj->setAmount(
                $originalAmountObj->getAmount() - $shoppingcartTotal->getAmount()
            );

            // Rename old voucher code
            $renameIdx = 1;

            while (DataObject::get_one('SilvercartVoucher', sprintf("code = '%s'", $originalVoucherCode.'_'.$renameIdx))) {
                $renameIdx++;
            }
            $this->code = $originalVoucherCode.'_'.$renameIdx;
            $this->write();
        
            // Create new voucher and bind it to the customer
            $newVoucher = new SilvercartAbsoluteRebateGiftVoucher();
            $newVoucher->setField('quantity', 1);
            $newVoucher->setField('isActive', 1);
            $newVoucher->setField('code', $originalVoucherCode);
            $newVoucher->setField('valueAmount', $restAmountObj->getAmount());
            $newVoucher->setField('valueCurrency', $restAmountObj->getCurrency());
            $newVoucher->setField('MemberID', $member->ID);
            $newVoucher->setField('isBoundToCustomer', 1);
            $newVoucher->setField('minimumShoppingCartValueAmount', $this->minimumShoppingCartValueAmount);
            $newVoucher->setField('minimumShoppingCartValueCurrency', $this->minimumShoppingCartValueCurrency);
            $newVoucher->setField('maximumShoppingCartValueAmount', $this->maximumShoppingCartValueAmount);
            $newVoucher->setField('maximumShoppingCartValueCurrency', $this->maximumShoppingCartValueCurrency);
            $newVoucher->setField('quantityRedeemed', 0);
            $newVoucher->setField('SilvercartTaxID', $this->SilvercartTaxID);
            $newVoucher->setField('SilvercartAbsoluteRebateGiftVoucherBlueprintID', $this->SilvercartAbsoluteRebateGiftVoucherBlueprintID);
            $newVoucher->write();
         }
    }
    
    /**
     * Returns a dataobjectset for the display of the voucher positions in the
     * shoppingcart.
     *
     * @param SilvercartShoppingCart $silvercartShoppingCart       The shoppingcart object
     * @param Bool                   $taxable                      Indicates if taxable or nontaxable entries should be returned
     * @param array                  $excludeShoppingCartPositions Positions that shall not be counted
     * @param Bool                   $createForms                  Indicates wether the form objects should be created or not
     *
     * @return DataObjectSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function getSilvercartShoppingCartPositions(SilvercartShoppingCart $silvercartShoppingCart, $taxable = true, $excludeShoppingCartPositions = false, $createForms = true) {
        $positions = new DataObjectSet();
        
        if ($excludeShoppingCartPositions &&
            in_array($this->ID, $excludeShoppingCartPositions)) {
            return $positions;
        }
        $controller             = Controller::curr();
        $removeCartFormRendered = '';
        $tax                    = $this->SilvercartTax();

        if ( (!$taxable && !$tax) ||
             (!$taxable && $tax->Rate == 0) ||
             ($taxable && $tax && $tax->Rate > 0) ) {

            if ($createForms) {
                $removeCartForm = $controller->getRegisteredCustomHtmlForm('SilvercartVoucherRemoveFromCartForm'.$this->ID);

                if ($removeCartForm) {
                    $removeCartForm->setFormFieldValue('SilvercartVoucherID', $this->ID);
                    $removeCartFormRendered = Controller::curr()->InsertCustomHtmlForm('SilvercartVoucherRemoveFromCartForm'.$this->ID);
                }
            }

            $title = self::$singular_name.' (Code: '.$this->code.')';

            // The shopppingcart total may not be below 0
            $shoppingcartTotal = $silvercartShoppingCart->getAmountTotalWithoutFees(null, array($this->ID));
            $originalAmount    = $this->value->getAmount();
            if ($this->value->getAmount() >= $shoppingcartTotal->getAmount()) {
                $this->value->setAmount(
                    $shoppingcartTotal->getAmount()
                );

                $originalAmountObj = new Money();
                $originalAmountObj->setAmount($originalAmount);

                $restAmountObj = new Money();
                $restAmountObj->setAmount(
                    $originalAmount - $this->value->getAmount()
                );

                $title .= sprintf(
                    "<br />Ursprünglicher Wert: %s, Restwert: %s",
                    $originalAmountObj->Nice(),
                    $restAmountObj->Nice()
                );
            }

            $positions->push(
                new DataObject(
                    array(
                        'ID'                    => $this->ID,
                        'Name'                  => $title,
                        'ShortDescription'      => $this->code,
                        'LongDescription'       => $this->code,
                        'Currency'              => $this->value->getCurrency(),
                        'Price'                 => $this->value->getAmount() * -1,
                        'PriceFormatted'        => '-'.$this->value->Nice(),
                        'PriceTotal'            => $this->value->getAmount() * -1,
                        'PriceTotalFormatted'   => '-'.$this->value->Nice(),
                        'Quantity'              => '1',
                        'removeFromCartForm'    => $removeCartFormRendered,
                        'TaxRate'               => $this->SilvercartTax()->Rate,
                        'TaxAmount'             => $this->value->getAmount() - ($this->value->getAmount() / (100 + $this->SilvercartTax()->Rate) * 100),
                        'Tax'                   => $this->SilvercartTax()
                    )
                )
            );
        }

        return $positions;
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
    public function getSilvercartShoppingCartTotal() {
        $amount = new Money();
        $member = Member::currentUser();

        $silvercartVoucherShoppingCartPosition = silvercartVoucherShoppingCartPosition::get($member->SilvercartShoppingCart()->ID, $this->ID);

        if ($silvercartVoucherShoppingCartPosition &&
            $silvercartVoucherShoppingCartPosition->implicatePosition) {

            $amount->setAmount($this->value->getAmount() * -1);
            $amount->setCurrency($this->value->getCurrency());
        } else {
            $amount->setAmount(0);
            $amount->setCurrency($this->value->getCurrency());
        }

        return $amount;
    }

    /**
     * Redefine input fields for the backend.
     *
     * @param array $params Additional parameters
     *
     * @return FieldSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 21.01.2011
     */
    public function getCMSFields($params = null) {
        $fields = parent::getCMSFields($params);

        $fields->removeByName('quantityRedeemed');
        $quantityRedeemedField = new LiteralField('quantityRedeemed', '<br />' . _t('SilvercartVoucher.REDEEMED_VOUCHERS', 'Redeemed vouchers: ') . ($this->quantityRedeemed ? $this->quantityRedeemed : '0'));

        $fields->addFieldToTab('Root.Main', $quantityRedeemedField);

        return $fields;
    }
}
