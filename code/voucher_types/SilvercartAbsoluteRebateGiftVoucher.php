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
     * Singular name
     *
     * @var string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 24.01.2011
     */
    public static $singular_name = 'Geschenkgutschein';

    /**
     * Plural name
     *
     * @var string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 24.01.2011
     */
    public static $plural_name   = 'Geschenkgutscheine';

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

    /**
     * Summary fields for the model admin table.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 21.01.2011
     */
    public static $summary_fields = array(
        'quantity'
    );

    /**
     * Summary field labels for the model admin.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 21.01.2011
     */
    public static $field_labels = array(
        'quantity'      => 'Anzahl'
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

    // ------------------------------------------------------------------------
    // Methods
    // ------------------------------------------------------------------------

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
            $shoppingcartTotal = $silvercartShoppingCart->getAmountTotal(null, array($this->ID));
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
                        'SilvercartTaxRate'     => $this->SilvercartTax()->Rate,
                        'SilvercartTaxAmount'   => $this->value->getAmount() - ($this->value->getAmount() / (100 + $this->SilvercartTax()->Rate) * 100),
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
        $quantityRedeemedField = new LiteralField('quantityRedeemed', '<br />Eingel&ouml;ste Gutscheine: '.($this->quantityRedeemed ? $this->quantityRedeemed : '0'));

        $fields->addFieldToTab('Root.Main', $quantityRedeemedField);

        // Remove ProductTab and replace with DOM
        $fields->removeFieldFromTab('Root', 'SilvercartProducts');

        $productTable = new HasOneComplexTableField(
            $this,
            'SilvercartProducts',
            'SilvercartGiftVoucherProduct',
            SilvercartProduct::$summary_fields,
            'getCMSFields_forPopup',
            '',
            'SilvercartProduct.ID DESC',
            ''
        );

        $fields->addFieldToTab('Root.Products', $productTable);

        return $fields;
    }

    /**
     * In order to succeed the customer must be fully registered. Otherwise
     * the binding of the voucher to the customer doesn't make sense.
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
     * @since 13.04.2011
     */
    public function checkifAllowedInShoppingCart($voucherCode, Member $member, SilvercartShoppingCart $silvercartShoppingCart) {
        $checkStatus = parent::checkifAllowedInShoppingCart($voucherCode, $member, $silvercartShoppingCart);

        if (!SilvercartCustomerRole::currentRegisteredCustomer()) {
            $checkStatus['error']       = true;
            $checkStatus['messages'][]  = sprintf(
                _t('SilvercartVoucher.ERRORMESSAGE-CUSTOMER_MUST_BE_REGISTERED'),
                SilvercartPage_Controller::PageByIdentifierCodeLink('SilvercartRegistrationPage').
                    '?backlink='.urlencode(Controller::curr()->Link()).
                    '&backlinkText='.urlencode(_t('SilvercartCheckoutFormStep1NewCustomerForm.CONTINUE_WITH_CHECKOUT')).
                    '&optInTempText='.urlencode(_t('SilvercartCheckoutFormStep1NewCustomerForm.OPTIN_TEMP_TEXT'))
            );
        }

        return $checkStatus;
    }
}
