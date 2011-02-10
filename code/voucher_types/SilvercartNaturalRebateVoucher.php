<?php
/**
 * Extends the voucher class for natural rebates, i.e. articles.
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
        'Articles'                      => 'Article'
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
     * @param ShoppingCart $shoppingCart The shoppingcart object
     * @param Bool         $taxable      Indicates if taxable or nontaxable entries should be returned
     *
     * @return DataObjectSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.01.2011
     */
    public function getShoppingCartPositions(ShoppingCart $shoppingCart, $taxable = true) {
        $controller             = Controller::curr();
        $removeCartFormRendered = '';
        $positions              = new DataObjectSet();
        $tax                    = $this->Tax();

        if ( (!$taxable && !$tax) ||
             (!$taxable && $tax->Rate == 0) ||
             ($taxable && $tax && $tax->Rate > 0) ) {

            $removeCartForm = $controller->getRegisteredCustomHtmlForm('SilvercartVoucherRemoveFromCartForm'.$this->ID);

            if ($removeCartForm) {
                $removeCartForm->setFormFieldValue('VoucherID', $this->ID);
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
                        'TaxRate'               => $this->Tax()->Rate,
                        'TaxAmount'             => 0,
                        'Tax'                   => $this->Tax()
                    )
                )
            );

            // Display related articles
            foreach ($this->Articles() as $article) {
                $positions->push(
                    new DataObject(
                        array(
                            'ID'                    => $article->ID,
                            'Name'                  => $article->Title,
                            'ShortDescription'      => $article->ShortDescription,
                            'LongDescription'       => $article->LongDescription,
                            'Currency'              => '',
                            'Price'                 => $article->Price->getAmount(),
                            'PriceFormatted'        => $article->Price->Nice(),
                            'PriceTotal'            => 0,
                            'PriceTotalFormatted'   => '',
                            'Quantity'              => '1',
                            'removeFromCartForm'    => '',
                            'TaxRate'               => '',
                            'TaxAmount'             => 0,
                            'Tax'                   => ''
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
    public function getShoppingCartTotal() {
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

        // Remove Article Tab and replace with DOM
        $fields->removeFieldFromTab('Root', 'Articles');

        $articleTable = new HasManyComplexTableField(
            $this,
            'Articles',
            'Article',
            Article::$summary_fields,
            'getCMSFields_forPopup',
            '',
            'Article.ID DESC',
            ''
        );
        
        $fields->addFieldToTab('Root.Articles', $articleTable);

        return $fields;
    }
}