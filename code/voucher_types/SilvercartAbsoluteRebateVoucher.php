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
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 20.01.2011
     */
    public static $db = array(
        'value' => 'Money'
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
     * @since 19.10.2011
     */
    public function plural_name() {
        if (_t('SilvercartAbsoluteRebateVoucher.PLURALNAME')) {
            $plural_name = _t('SilvercartAbsoluteRebateVoucher.PLURALNAME');
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
        if (_t('SilvercartAbsoluteRebateVoucher.SINGULARNAME')) {
            $singular_name = _t('SilvercartAbsoluteRebateVoucher.SINGULARNAME');
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
                'value' => _t('SilvercartAbsoluteRebateVoucher.VALUE'),
            )
        );
    }

    /**
     * Returns a dataobjectset for the display of the voucher positions in the
     * shoppingcart.
     *
     * @param SilvercartShoppingCart $silvercartShoppingCart       The shoppingcart object
     * @param Bool                   $taxable                      Indicates if taxable or nontaxable entries should be returned
     * @param array                  $excludeShoppingCartPositions Positions that shall not be counted; can be the ID or the className of the position
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
            if (SilvercartConfig::PriceType() == 'gross') {
                $shoppingcartTotal = $silvercartShoppingCart->getTaxableAmountGrossWithoutFeesAndCharges(false, $excludeShoppingCartPositions);
                $originalAmount = $this->value->getAmount();

            } else {
                $shoppingcartTotal = $silvercartShoppingCart->getTaxableAmountNetWithoutFeesAndCharges(false, $excludeShoppingCartPositions);
                $originalAmount = $priceNet->getAmount();
            }

            if ($originalAmount >= $shoppingcartTotal->getAmount()) {
                $this->value->setAmount($shoppingcartTotal->getAmount());
                $priceNet->setAmount($shoppingcartTotal->getAmount());

                $originalAmountObj = new Money();
                $originalAmountObj->setAmount($originalAmount);

                $restAmountObj = new Money();
                $restAmountObj->setAmount(
                    $originalAmount - $this->value->getAmount()
                );

                $title .= sprintf(
                    "<br />%s: %s",
                    _t('SilvercartVoucher.ORIGINAL_VALUE'),
                    $originalAmountObj->Nice()
                );
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

            $priceNetTotal = $priceNet;
            $position      = new SilvercartVoucherPrice();
            
            $position->ID = $this->ID;
            $position->Name                  = $title;
            $position->ShortDescription      = $this->code;
            $position->LongDescription       = $this->code;
            $position->Currency              = $this->value->getCurrency();
            $position->Price                 = $this->value->getAmount();
            $position->PriceFormatted        = $this->value->Nice();
            $position->PriceTotal            = $this->value->getAmount();
            $position->PriceTotalFormatted   = $this->value->Nice();
            $position->PriceNet              = $priceNet->getAmount();
            $position->PriceNetFormatted     = $priceNet->Nice();
            $position->PriceNetTotal         = $priceNetTotal->getAmount();
            $position->PriceNetTotalFormatted= $priceNetTotal->Nice();
            $position->Quantity              = 1;
            $position->removeFromCartForm    = $removeCartFormRendered;
            $position->TaxRate               = $this->SilvercartTax()->Rate;
            $position->TaxAmount             = -$taxAmount;
            $position->Tax                   = $this->SilvercartTax();

            $positions->push($position);

            if (!in_array($this->ID, self::$alreadyHandledPositionIDs)) {
                self::$alreadyHandledPositionIDs[] = $this->ID;
            }

            if (is_array(self::$alreadyHandledPositions)) {
                self::$alreadyHandledPositions = new DataObjectSet();
            }

            self::$alreadyHandledPositions->push($position);
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

        $silvercartVoucherShoppingCartPosition = SilvercartVoucherShoppingCartPosition::get($member->SilvercartShoppingCart()->ID, $this->ID);
        
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
