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
 * Extends the voucher class for natural rebates, i.e. products.
 *
 * @package Silvercart
 * @subpackage Vouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 20.01.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartNaturalRebateVoucher extends SilvercartVoucher {

    /**
     * Has many relationships.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 20.01.2011
     */
    public static $has_many = array(
        'SilvercartProducts' => 'SilvercartProduct'
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
        if (_t('SilvercartNaturalRebateVoucher.PLURALNAME')) {
            $plural_name = _t('SilvercartNaturalRebateVoucher.PLURALNAME');
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
        if (_t('SilvercartNaturalRebateVoucher.SINGULARNAME')) {
            $singular_name = _t('SilvercartNaturalRebateVoucher.SINGULARNAME');
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
                'SilvercartProducts' => _t('SilvercartProduct.PLURALNAME'),
            )
        );
    }

    /**
     * Returns a dataobjectset for the display of the voucher positions in the
     * shoppingcart.
     *
     * @param SilvercartShoppingCart $silvercartShoppingCart       ShoppingCart The shoppingcart object
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

            $positions->push(
                new DataObject(
                    array(
                        'ID'                    => $this->ID,
                        'Name'                  => self::$singular_name.' (Code: '.$this->code.')',
                        'ShortDescription'      => $this->code,
                        'LongDescription'       => $this->code,
                        'Currency'              => '',
                        'Price'                 => 0,
                        'PriceFormatted'        => '',
                        'PriceTotal'            => 0,
                        'PriceTotalFormatted'   => '',
                        'Quantity'              => '1',
                        'removeFromCartForm'    => $removeCartFormRendered,
                        'SilvercartTaxRate'     => $this->SilvercartTax()->Rate,
                        'SilvercartTaxAmount'   => 0,
                        'SilvercartTax'         => $this->SilvercartTax()
                    )
                )
            );

            // Display related product
            foreach ($this->SilvercartProducts() as $SilvercartProduct) {
                $positions->push(
                    new DataObject(
                        array(
                            'ID'                    => $SilvercartProduct->ID,
                            'Name'                  => $SilvercartProduct->Title,
                            'ShortDescription'      => $SilvercartProduct->ShortDescription,
                            'LongDescription'       => $SilvercartProduct->LongDescription,
                            'Currency'              => '',
                            'Price'                 => $SilvercartProduct->Price->getAmount(),
                            'PriceFormatted'        => $SilvercartProduct->Price->Nice(),
                            'PriceTotal'            => 0,
                            'PriceTotalFormatted'   => '',
                            'Quantity'              => '1',
                            'removeFromCartForm'    => '',
                            'SilvercartTaxRate'     => '',
                            'SilvercartTaxAmount'   => 0,
                            'SilvercartTax'         => ''
                        )
                    )
                );
            }
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
        $amount     = new Money();
        $currency   = new Zend_Currency(null, i18n::get_locale());

        $amount->setAmount(0);
        $amount->setCurrency($currency->getShortName(null, i18n::get_locale()));

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

        // Remove Product Tab and replace with DOM
        $fields->removeFieldFromTab('Root', 'SilvercartProducts');

        $productTable = new HasManyComplexTableField(
            $this,
            'SilvercartProducts',
            'SilvercartProduct',
            SilvercartProduct::$summary_fields,
            'getCMSFields_forPopup',
            '',
            'SilvercartProduct.ID DESC',
            ''
        );
        
        $productsTab = $fields->findOrMakeTab('Root.Products');
        $productsTab->setTitle(_t('SilvercartProduct.PLURALNAME'));
        $fields->addFieldToTab('Root.Products', $productTable);

        return $fields;
    }
}
