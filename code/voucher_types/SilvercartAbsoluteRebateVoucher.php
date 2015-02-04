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
class SilvercartAbsoluteRebateVoucher extends SilvercartVoucher {

    public static $alreadyHandledPositionIDs = array();
    public static $alreadyHandledPositions = array();

    /**
     * Attributes.
     *
     * @var array
     */
    public static $db = array(
        'value' => 'SilvercartMoney'
    );

    // ------------------------------------------------------------------------
    // Methods
    // ------------------------------------------------------------------------

    /**
     * Returns the translated plural name of the object. If no translation exists
     * the class name will be returned.
     *
     * @return string
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 28.08.2012
     */
    public function plural_name() {
        return SilvercartTools::plural_name_for($this);
    }

    /**
     * Returns the translated singular name of the object. If no translation exists
     * the class name will be returned.
     *
     * @return string
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 28.08.2012
     */
    public function singular_name() {
        return SilvercartTools::singular_name_for($this);
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
                'value' => _t('SilvercartAbsoluteRebateVoucher.VALUE'),
            )
        );
    }

    /**
     * Returns a SS_List for the display of the voucher positions in the
     * shoppingcart.
     *
     * @param SilvercartShoppingCart $silvercartShoppingCart       The shoppingcart object
     * @param Bool                   $taxable                      Indicates if taxable or nontaxable entries should be returned
     * @param array                  $excludeShoppingCartPositions Positions that shall not be counted; can be the ID or the className of the position
     * @param Bool                   $createForms                  Indicates wether the form objects should be created or not
     *
     * @return SS_List
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>,
     *         Sascha Koehler <skoehler@pixeltricks.de>
     * @since 03.02.2015
     */
    public function getSilvercartShoppingCartPositions(SilvercartShoppingCart $silvercartShoppingCart, $taxable = true, $excludeShoppingCartPositions = false, $createForms = true) {
        $positions = new ArrayList();
        if ($excludeShoppingCartPositions !== false &&
            (
                in_array($this->ID, $excludeShoppingCartPositions) ||
                in_array($this->class, $excludeShoppingCartPositions)
            )
           ) {
            return $positions;
        }

        $controller             = Controller::curr();
        $removeCartFormRendered = '';
        $removeCartFormName     = 'SilvercartVoucherRemoveFromCartForm'.$this->ID;
        $tax                    = $this->SilvercartTax();

        if (!$controller->isFrontendPage) {
            return $positions;
        }

        if ( (!$taxable && !$tax) ||
             (!$taxable && $tax->Rate == 0) ||
             ($taxable && $tax && $tax->Rate > 0) ) {

            if (in_array($this->ID, self::$alreadyHandledPositionIDs)) {
                $position = self::$alreadyHandledPositions->find('ID', $this->ID);

                if (method_exists(Controller::curr(), 'getEditableShoppingCart') &&
                    Controller::curr()->getEditableShoppingCart()) {

                    if (array_key_exists($removeCartFormName, Controller::curr()->getRegisteredCustomHtmlForms())) {
                        $removeCartFormRendered       = Controller::curr()->InsertCustomHtmlForm($removeCartFormName);
                        $position->removeFromCartForm = $removeCartFormRendered;
                    }
                }
                return $position;
            }

            if ($createForms) {
                $removeCartForm = $controller->getRegisteredCustomHtmlForm($removeCartFormName);

                if ($removeCartForm) {
                    $removeCartForm->setFormFieldValue('SilvercartVoucherID', $this->ID);
                    $removeCartFormRendered = Controller::curr()->InsertCustomHtmlForm($removeCartFormName);
                }
            }

            $title = $this->singular_name().' (Code: '.$this->code.')';

            $priceNetAmount = round($this->value->getAmount() / (100 + $this->SilvercartTax()->Rate) * 100, 4);
            $priceNet = new Money();
            $priceNet->setAmount($priceNetAmount);
            $priceNet->setCurrency(SilvercartConfig::DefaultCurrency());

            // The shopppingcart total may not be below 0
            $excludeShoppingCartPositions[] = $this->ID;
            $shoppingcartTotal = $silvercartShoppingCart->getTaxableAmountGrossWithoutFeesAndCharges(false, $excludeShoppingCartPositions);
            $originalAmount = $this->value->getAmount();
            
            if ($originalAmount >= $shoppingcartTotal->getAmount()) {
                
                $originalAmountObj = new Money();
                $originalAmountObj->setAmount($originalAmount);
                
                $title .= sprintf(
                    "<br />%s: %s",
                    _t('SilvercartVoucher.ORIGINAL_VALUE'),
                    $originalAmountObj->Nice()
                );
                
                $member = SilvercartCustomer::currentRegisteredCustomer();
                if ($member &&
                    !is_null($member->SilvercartVouchers()->find('ID', $this->ID))) {                
                    
                    $voucherOnMember = $member->SilvercartVouchers()->find('ID', $this->ID);
                    $this->value->setAmount((float)$voucherOnMember->remainingAmount);

                    $restAmountObj = new Money();
                    $restAmountObj->setAmount(
                        $voucherOnMember->remainingAmount
                    );
                    
                    $title .= sprintf(
                        "<br />%s: %s",
                        _t('SilvercartVoucher.REMAINING_CREDIT'),
                        $restAmountObj->Nice()
                    );
                    
                } else {
                    $this->value->setAmount($shoppingcartTotal->getAmount());
                    $priceNet->setAmount($shoppingcartTotal->getAmount());
                }
            }
            
            $taxAmount = (float) 0.0;

            if ($this->value->getAmount() > 0) {
                $amount = $this->value->getAmount();

                if (SilvercartConfig::PriceType() == 'gross') {
                    $taxAmount = (float) $amount - ($amount / (100 + $this->SilvercartTax()->Rate) * 100);
                } else {
                    $taxAmount = (float) ($priceNet->getAmount() * ($this->SilvercartTax()->Rate) / 100);
                }
            }
            
            $priceNet->setAmount($priceNet->getAmount() * -1);
            $this->value->setAmount($this->value->getAmount() * -1);
            
            
            $position = $this->createSilvercartVoucherPricePosition($title, $priceNet, $removeCartFormRendered, $taxAmount);
            $positions->push($position);

            if (!in_array($this->ID, self::$alreadyHandledPositionIDs)) {
                self::$alreadyHandledPositionIDs[] = $this->ID;
            }

            if (is_array(self::$alreadyHandledPositions)) {
                self::$alreadyHandledPositions = new ArrayList();
            }

            self::$alreadyHandledPositions->push($position);
        }
        
        return $positions;
    }
    
    /**
     * creates a SilvercartVoucherPriceObj and returns it
     * 
     * @param string $title                  Title of the object
     * @param Money  $priceNet               net price object
     * @param string $removeCartFormRendered renderd form to remove position from cart
     * @param Money  $taxAmount              tax amount obj
     * 
     * @return Money
     * 
     * @author Patrick Schneider <pschneider@pixeltricks.de>
     * @since 05.12.2012
     */
    protected function createSilvercartVoucherPricePosition($title, $priceNet, $removeCartFormRendered, $taxAmount) {
        $priceNetTotal = $priceNet;
        $voucherPriceObj      = new SilvercartVoucherPrice();

        $voucherPriceObj->ID = $this->ID;
        $voucherPriceObj->Name                  = $title;
        $voucherPriceObj->ShortDescription      = $this->code;
        $voucherPriceObj->LongDescription       = $this->code;
        $voucherPriceObj->Currency              = $this->value->getCurrency();
        $voucherPriceObj->Price                 = $this->value->getAmount();
        $voucherPriceObj->PriceFormatted        = $this->value->Nice();
        $voucherPriceObj->PriceTotal            = $this->value->getAmount();
        $voucherPriceObj->PriceTotalFormatted   = $this->value->Nice();
        $voucherPriceObj->PriceNet              = $priceNet->getAmount();
        $voucherPriceObj->PriceNetFormatted     = $priceNet->Nice();
        $voucherPriceObj->PriceNetTotal         = $priceNetTotal->getAmount();
        $voucherPriceObj->PriceNetTotalFormatted= $priceNetTotal->Nice();
        $voucherPriceObj->Quantity              = 1;
        $voucherPriceObj->removeFromCartForm    = $removeCartFormRendered;
        $voucherPriceObj->TaxRate               = $this->SilvercartTax()->Rate;
        $voucherPriceObj->TaxAmount             = -$taxAmount;
        $voucherPriceObj->Tax                   = $this->SilvercartTax();
        $voucherPriceObj->ProductNumber         = $this->ProductNumber;
        
        return $voucherPriceObj;
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

        $silvercartVoucherShoppingCartPosition = SilvercartVoucherShoppingCartPosition::getVoucherShoppingCartPosition($member->SilvercartShoppingCart()->ID, $this->ID);

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
     */
    public function getCMSFields($params = null) {
        $fields = parent::getCMSFields(
                array_merge(
                        array(
                            'fieldClasses' => array(
                                'value' => 'SilvercartMoneyField',
                            ),
                        ),
                        (array) $params
                )
        );

        $fields->removeByName('quantityRedeemed');
        $quantityRedeemedField = new LiteralField('quantityRedeemed', '<br />' . _t('SilvercartVoucher.REDEEMED_VOUCHERS', 'Redeemed vouchers: ') . ($this->quantityRedeemed ? $this->quantityRedeemed : '0'));

        $fields->addFieldToTab('Root.Main', $quantityRedeemedField);

        return $fields;
    }

    /**
     * splits a value of a voucher to make sure a voucher can be used until it
     * has a value of 0
     * 
     * @param float $currentRemainingAmount current remaining amount for customer <->member
     * @param float $amountToReduce         amount to reduce
     *
     * @return float
     *
     * @author Patrick Schneider <pschneider@pixeltricks.de>
     * @since 03.12.2012
     */
    protected function doSplitValue($currentRemainingAmount, $amountToReduce) {
            (float)$remainingAmount = (float)$currentRemainingAmount - ($amountToReduce*-1);
            if ($remainingAmount < 0.0) {
                // this user can't reuse this voucher anymore
                $remainingAmount = 0.0;
            }
            return $remainingAmount;
    }

    /**
     * This method gets called when converting the shoppingcart positions to
     * order positions.
     *
     * @param SilvercartShoppingCart         $silvercartShoppingCart the shoppingcart object
     * @param SilvercartShoppingCartPosition $shoppingCartPosition   position of the shoppingcart which contains the voucher
     * @param SilvercartVoucher              $originalVoucher        the original voucher
     * @param Member                         $member                 member object
     *
     * @return void
     *
     * @author Patrick Schneider <pschneider@pixeltricks.de>
     * @copyright 2012 pixeltricks GmbH
     * @since 03.12.2012
     *
     */
    public function convert(SilvercartShoppingCart $silvercartShoppingCart, SilvercartVoucherShoppingCartPosition $shoppingCartPosition, SilvercartVoucher $originalVoucher, Member $member) {
        if (SilvercartCustomer::currentRegisteredCustomer()) {
            // only do this for registered customers
            $currentRemainingAmount = null;
            $amountToReduce = $shoppingCartPosition->SilvercartVoucher()->value->getAmount();
            $voucherOnMember = $member->SilvercartVouchers()->find('ID', $shoppingCartPosition->SilvercartVoucherID);
            if (!$voucherOnMember) {
                // this voucher is unused yet by this customer, connect to customer
                $member->SilvercartVouchers()->add($originalVoucher);
                $currentRemainingAmount = $originalVoucher->value->getAmount();
            } else {
                $currentRemainingAmount = $voucherOnMember->remainingAmount;
            }
            $newRemainingAmount = $this->doSplitValue($currentRemainingAmount, $amountToReduce);
            $member->SilvercartVouchers()->add(
                $originalVoucher, 
                array(
                    'remainingAmount' => $newRemainingAmount
                )
            );
        }
    }
    
    /**
     * returns the relation object for given member and voucherID
     * null if it does not exist
     * 
     * @param Member $member    member object to search on
     * @param int    $voucherID voucherID to search for
     * 
     * @return ViewableData
     */
    protected function getVoucherOnMember(Member $member, int $voucherID) {
        return $member->SilvercartVouchers()->find('ID', $voucherID);
    }
    
    /**
     * can be used to return if a voucher is already fully redeemd,
     * set error message in checkifAllowedInShoppingCart()
     * 
     * @param Member $member    the member object
     * @param String $voucherID used voucher code to check for
     * 
     * @return bool
     * 
     * @author Patrick Schneider <pschneider@pixeltricks.de>
     * @since 06.12.2012
     */
    protected function isCompletelyRedeemedAlready(Member $member, String $voucherID) {
        $isFullyRedeemedAlready = false;
        if (SilvercartCustomer::currentRegisteredCustomer()) {
            $voucherOnMember = $member->SilvercartVouchers()->find('ID', $voucherID);
            if ($voucherOnMember &&
                $voucherOnMember->remainingAmount == 0.0) {
                $isFullyRedeemedAlready = true;
            }
        }
        return $isFullyRedeemedAlready;
    }
}
