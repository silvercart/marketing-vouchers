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
     * Singular name
     *
     * @var string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 10.02.2011
     */
    public static $singular_name = 'Rabattgutschein';

    /**
     * Plural name
     *
     * @var string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 10.02.2011
     */
    public static $plural_name   = 'Rabattgutscheine';

    /**
     * Attributes.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 20.01.2011
     */
    public static $db = array(
        'valueInPercent'                    => 'Int'
    );

    /**
     * Summary fields for the model admin table.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 10.02.2011
     */
    public static $summary_fields = array(
        'code',
        'valueInPercent'
    );

    /**
     * Summary field labels for the model admin.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 10.02.2011
     */
    public static $field_labels = array(
        'code'              => 'Gutscheincode',
        'ValueInPercent'    => 'Rabatt in Prozent'
    );

    // ------------------------------------------------------------------------
    // Methods
    // ------------------------------------------------------------------------

    /**
     * Returns a dataobjectset for the display of the voucher positions in the
     * shoppingcart.
     *
     * @param SilvercartShoppingCart $silvercartShoppingCart        The shoppingcart object
     * @param Bool                   $taxable                       Indicates if taxable or nontaxable entries should be returned
     * @param array                  $excludeShoppingCartPositions  Positions that shall not be counted
     * @param Bool                   $createForms                   Indicates wether the form objects should be created or not
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

            $currency           = new Zend_Currency(null, i18n::get_locale());
            if ($createForms) {
                $removeCartForm = $controller->getRegisteredCustomHtmlForm('SilvercartVoucherRemoveFromCartForm'.$this->ID);
            }
            $silvercartShoppingCartAmount = $silvercartShoppingCart->getTaxableAmountGrossWithoutFees(array('SilvercartVoucher'))->getAmount();
            $rebateAmount       = ($silvercartShoppingCartAmount / 100 * $this->valueInPercent);
            $rebate             = new Money();
            $rebate->setAmount($rebateAmount);
            $rebate->setCurrency($currency->getShortName(null, i18n::get_locale()));

            if ($createForms) {
                if ($removeCartForm) {
                    $removeCartForm->setFormFieldValue('SilvercartVoucherID', $this->ID);
                    $removeCartFormRendered = Controller::curr()->InsertCustomHtmlForm('SilvercartVoucherRemoveFromCartForm'.$this->ID);
                }
            }

            $positions->push(
                new DataObject(
                    array(
                        'ID'                    => $this->ID,
                        'Name'                  => self::$singular_name.' (Code: '.$this->code.')',
                        'ShortDescription'      => $this->code,
                        'LongDescription'       => $this->code,
                        'Currency'              => $currency->getShortName(null, i18n::get_locale()),
                        'Price'                 => $rebateAmount * -1,
                        'PriceFormatted'        => '-'.$rebate->Nice(),
                        'PriceTotal'            => $rebateAmount * -1,
                        'PriceTotalFormatted'   => '-'.$rebate->Nice(),
                        'Quantity'              => '1',
                        'removeFromCartForm'    => $removeCartFormRendered,
                        'SilvercartTaxRate'     => $this->SilvercartTax()->Rate,
                        'SilvercartTaxAmount'   => $rebateAmount - ($rebateAmount / (100 + $this->SilvercartTax()->Rate) * 100),
                        'SilvercartTax'         => $this->SilvercartTax()
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
        $amount             = new Money();
        $member             = Member::currentUser();
        $silvercartShoppingCartAmount = $member->SilvercartShoppingCart()->getTaxableAmountGrossWithoutFees(array('SilvercartVoucher'))->getAmount();
        $rebateAmount       = ($silvercartShoppingCartAmount / 100 * $this->valueInPercent);
        $rebate             = new Money();
        $rebate->setAmount($rebateAmount);

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
        $quantityRedeemedField = new LiteralField('quantityRedeemed', '<br />Eingel&ouml;ste Gutscheine: '.($this->quantityRedeemed ? $this->quantityRedeemed : '0'));

        $fields->addFieldToTab('Root.Main', $quantityRedeemedField);

        return $fields;
    }
}
