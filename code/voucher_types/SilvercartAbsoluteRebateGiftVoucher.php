<?php
/**
 * Extends the voucher class for absolute rebates, i.e. 50,00 Eur.
 *
 * @package SilvercartVouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 20.01.2011
 * @license none
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
        'Customer'                                     => 'Member'
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
                        'Currency'              => $this->value->getCurrency(),
                        'Price'                 => $this->value->getAmount() * -1,
                        'PriceFormatted'        => '-'.$this->value->Nice(),
                        'PriceTotal'            => $this->value->getAmount() * -1,
                        'PriceTotalFormatted'   => '-'.$this->value->Nice(),
                        'Quantity'              => '1',
                        'removeFromCartForm'    => $removeCartFormRendered,
                        'TaxRate'               => $this->Tax()->Rate,
                        'TaxAmount'             => $this->value->getAmount() - ($this->value->getAmount() / (100 + $this->Tax()->Rate) * 100),
                        'Tax'                   => $this->Tax()
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
    public function getShoppingCartTotal() {
        $amount = new Money();
        $member = Member::currentUser();

        $silvercartVoucherShoppingCartPosition = SilvercartVoucherShoppingCartPosition::get($member->shoppingCart()->ID, $this->ID);

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

        $articleTable = new HasOneComplexTableField(
            $this,
            'Articles',
            'SilvercartGiftVoucherArticle',
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