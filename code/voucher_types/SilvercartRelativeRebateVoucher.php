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
 * Extends the voucher class for relative rebates that are subtracted from
 * the shoppingcart total sum (e.g. 10%).
 *
 * @package Silvercart
 * @subpackage Vouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 20.01.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartRelativeRebateVoucher extends SilvercartVoucher {

    /**
     * Attributes.
     *
     * @var array
     */
    public static $db = array(
        'valueInPercent'                    => 'Int'
    );

    /**
     * Summary fields for the model admin table.
     *
     * @var array
     */
    public static $summary_fields = array(
        'code',
        'valueInPercent'
    );
    
    /**
     * Addition to add to title
     *
     * @var string
     */
    protected $addToTitle = '';

    /**
     * Summary field labels for the model admin.
     *
     * @param boolean $includerelations a boolean value to indicate if the labels returned include relation fields
     * 
     * @return array
     *
     * @author Patrick Schneider <pschneider@pixeltricks.de>
     * @since 15.11.2011
     */
    public function fieldLabels($includerelations = true) {
        return array_merge(
                parent::fieldLabels($includerelations),
                array(
                    'code'              => _t('SilvercartVoucher.CODE', 'Voucher code'),
                    'valueInPercent'    => _t('SilvercartVoucher.VALUE_IN_PERCENT', 'Rebate value in percent')
                )
        );
    }
    
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
        if (_t('SilvercartRelativeRebateVoucher.PLURALNAME')) {
            $plural_name = _t('SilvercartRelativeRebateVoucher.PLURALNAME');
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
        if (_t('SilvercartRelativeRebateVoucher.SINGULARNAME')) {
            $singular_name = _t('SilvercartRelativeRebateVoucher.SINGULARNAME');
        } else {
            $singular_name = parent::singular_name();
        }
        return $singular_name;
    }

    // ------------------------------------------------------------------------
    // Methods
    // ------------------------------------------------------------------------
    
    /**
     * Returns the amount to reduce from shopping cart-
     * 
     * @param SilvercartShoppingCart $cart Cart to get amount from
     * 
     * @return float
     */
    protected function getAmountToReduce($cart) {
        
        $amountToReduce = 0;
        $positionNums   = array();
        
        if ($this->RestrictValueToProduct &&
            ($this->RestrictToSilvercartProduct()->Count() > 0 ||
             $this->RestrictToSilvercartProductGroupPage()->Count() > 0)) {
            
            if ($this->RestrictToSilvercartProduct()->Count() > 0) {

                $productIDs = array_keys($this->RestrictToSilvercartProduct()->map());
                foreach ($cart->SilvercartShoppingCartPositions() as $position) {
                    if (in_array($position->SilvercartProductID, $productIDs)) {
                        $amountToReduce += $position->getPrice()->getAmount();
                        $positionNums[] = $position->getProductNumberShop();
                    }
                }

            }
            if ($this->RestrictToSilvercartProductGroupPage()->Count() > 0) {

                $productGroupIDs = array_keys($this->RestrictToSilvercartProductGroupPage()->map());
                foreach ($cart->SilvercartShoppingCartPositions() as $position) {
                    $product = $position->SilvercartProduct();
                    if ($product instanceof SilvercartProduct &&
                        $product->isInDB()) {
                        if (in_array($product->SilvercartProductGroupID, $productGroupIDs)) {
                            $amountToReduce += $position->getPrice()->getAmount();
                            $positionNums[] = $position->getProductNumberShop();
                        } elseif ($product->SilvercartProductGroupMirrorPages()->Count() > 0) {
                            foreach ($product->SilvercartProductGroupMirrorPages() as $mirroredGroup) {
                                if (in_array($mirroredGroup->ID, $productGroupIDs)) {
                                    $amountToReduce += $position->getPrice()->getAmount();
                                    $positionNums[] = $position->getProductNumberShop();
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $amountToReduce   = $cart->getTaxableAmountGrossWithoutFees(array('SilvercartVoucher'))->getAmount();
        }
        
        if (count($positionNums) > 0) {
            $this->addToTitle = sprintf(
                    _t('SilvercartVoucher.ValueForPositions'),
                    $this->valueInPercent,
                    implode(', ', $positionNums)
            );
        } else {
            $this->addToTitle = sprintf(
                    _t('SilvercartVoucher.ValueForCart'),
                    $this->valueInPercent
            );
        }
        
        return $amountToReduce;
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
     * @author Sebastian Diel <sdiel@pixeltricks.de>,
     *         Sascha Koehler <skoehler@pixeltricks.de>
     * @since 26.03.2014
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
            }

            $amountToReduce = $this->getAmountToReduce($silvercartShoppingCart);
            $rebateAmount   = round($amountToReduce / 100 * $this->valueInPercent, 2);
            $rebate         = new Money();
            $rebate->setAmount($rebateAmount);
            $rebate->setCurrency(SilvercartConfig::DefaultCurrency());

            if ($createForms) {
                if ($removeCartForm) {
                    $removeCartForm->setFormFieldValue('SilvercartVoucherID', $this->ID);
                    $removeCartFormRendered = Controller::curr()->InsertCustomHtmlForm('SilvercartVoucherRemoveFromCartForm'.$this->ID);
                }
            }

            $position = new SilvercartVoucherPrice();
            
            $position->ID                    = $this->ID;
            $position->Name                  = $this->singular_name() . ' (Code: '.$this->code.') ' . $this->addToTitle;
            $position->ShortDescription      = $this->code;
            $position->LongDescription       = $this->code;
            $position->Currency              = SilvercartConfig::DefaultCurrency();
            $position->Price                 = $rebateAmount * -1;
            $position->PriceFormatted        = '-'.$rebate->Nice();
            $position->PriceTotal            = $rebateAmount * -1;
            $position->PriceTotalFormatted   = '-'.$rebate->Nice();
            $position->PriceNet              = $rebateAmount * -1;
            $position->PriceNetFormatted     = '-'.$rebate->Nice();
            $position->PriceNetTotal         = $rebateAmount * -1;
            $position->PriceNetTotalFormatted= '-'.$rebate->Nice();
            $position->Quantity              = 1;
            $position->removeFromCartForm    = $removeCartFormRendered;
            $position->TaxRate               = $this->SilvercartTax()->Rate;
            $position->TaxAmount             = ($rebateAmount - ($rebateAmount / (100 + $this->SilvercartTax()->Rate) * 100)) * -1;
            $position->Tax                   = $this->SilvercartTax();
            $position->ProductNumber         = $this->ProductNumber;

            $positions->push($position);
        }

        return $positions;
    }

    /**
     * Returns the amount to consider in the shopping cart total calculation.
     *
     * @return Money
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>,
     *         Sascha Koehler <skoehler@pixeltricks.de>
     * @since 26.03.2014
     */
    public function getSilvercartShoppingCartTotal() {
        
        $amount         = new Money();
        $member         = Member::currentUser();
        $amountToReduce = $this->getAmountToReduce($member->SilvercartShoppingCart());
        $rebateAmount   = ($amountToReduce / 100 * $this->valueInPercent);
        $rebate         = new Money();
        $rebate->setAmount($rebateAmount);
        $rebate->setCurrency(SilvercartConfig::DefaultCurrency());

        $silvercartVoucherShoppingCartPosition = SilvercartVoucherShoppingCartPosition::get($member->SilvercartShoppingCart()->ID, $this->ID);

        if ($silvercartVoucherShoppingCartPosition &&
            $silvercartVoucherShoppingCartPosition->implicatePosition) {

            $amount->setAmount($rebateAmount * -1);
            $amount->setCurrency($rebate->getCurrency());
        } else {
            $amount->setAmount(0);
            $amount->setCurrency($rebate->getCurrency());
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
