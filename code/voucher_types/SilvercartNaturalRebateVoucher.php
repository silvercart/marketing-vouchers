<?php
/**
 * Extends the voucher class for natural rebates, i.e. products.
 *
 * @package SilvercartVouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 20.01.2011
 * @license none
 */
class SilvercartNaturalRebateVoucher extends SilvercartVoucher {

    /**
     * Singular name
     *
     * @var string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 24.01.2011
     */
    public static $singular_name = 'Warengutschein';

    /**
     * Plural name
     *
     * @var string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 24.01.2011
     */
    public static $plural_name   = 'Warengutscheine';

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

    /**
     * Summary fields for the model admin table.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 21.01.2011
     */
    public static $summary_fields = array(
        'code',
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
        'code'          => 'Gutscheincode',
        'quantity'      => 'Anzahl'
    );

    // ------------------------------------------------------------------------
    // Methods
    // ------------------------------------------------------------------------

    /**
     * Returns a dataobjectset for the display of the voucher positions in the
     * shoppingcart.
     *
     * @param SilvercartShoppingCart $silvercartSilvercartShoppingCart The shoppingcart object
     * @param Bool                   $taxable                          Indicates if taxable or nontaxable entries should be returned
     *
     * @return DataObjectSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function getSilvercartShoppingCartPositions(SilvercartShoppingCart $silvercartSilvercartShoppingCart, $taxable = true) {
        $controller             = Controller::curr();
        $removeCartFormRendered = '';
        $positions              = new DataObjectSet();
        $tax                    = $this->SilvercartTax();

        if ( (!$taxable && !$tax) ||
             (!$taxable && $tax->Rate == 0) ||
             ($taxable && $tax && $tax->Rate > 0) ) {

            $removeCartForm = $controller->getRegisteredCustomHtmlForm('SilvercartVoucherRemoveFromCartForm'.$this->ID);

            if ($removeCartForm) {
                $removeCartForm->setFormFieldValue('SilvercartVoucherID', $this->ID);
                $removeCartFormRendered = Controller::curr()->InsertCustomHtmlForm('SilvercartVoucherRemoveFromCartForm'.$this->ID);
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
     * @param array params Additional parameters
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

        // Remove Product Tab and replace with DOM
        $fields->removeFieldFromTab('Root', 'SilvercartProducts');

        $productTable = new HasManyComplexTableField(
            $this,
            'SilvercartProducts',
            'SilvercartProduct',
            SilvertcartProduct::$summary_fields,
            'getCMSFields_forPopup',
            '',
            'SilvertcartProduct.ID DESC',
            ''
        );
        
        $fields->addFieldToTab('Root.Products', $productTable);

        return $fields;
    }
}
