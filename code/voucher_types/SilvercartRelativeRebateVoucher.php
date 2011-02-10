<?php
/**
 * Extends the voucher class for relative rebates that are subtracted from
 * the shoppingcart total sum (e.g. 10%).
 *
 * @package SilvercartVouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 20.01.2011
 * @license none
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

            $currency           = new Zend_Currency(null, i18n::get_locale());
            $removeCartForm     = $controller->getRegisteredCustomHtmlForm('SilvercartVoucherRemoveFromCartForm'.$this->ID);
            $shoppingCartAmount = $shoppingCart->getTaxableAmountGrossWithoutFees(array('SilvercartVoucher'))->getAmount();
            $rebateAmount       = ($shoppingCartAmount / 100 * $this->valueInPercent);
            $rebate             = new Money();
            $rebate->setAmount($rebateAmount);
            $rebate->setCurrency($currency->getShortName(null, i18n::get_locale()));

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
                        'Currency'              => $currency->getShortName(null, i18n::get_locale()),
                        'Price'                 => $rebateAmount * -1,
                        'PriceFormatted'        => '-'.$rebate->Nice(),
                        'PriceTotal'            => $rebateAmount * -1,
                        'PriceTotalFormatted'   => '-'.$rebate->Nice(),
                        'Quantity'              => '1',
                        'removeFromCartForm'    => $removeCartFormRendered,
                        'TaxRate'               => $this->Tax()->Rate,
                        'TaxAmount'             => $rebateAmount - ($rebateAmount / (100 + $this->Tax()->Rate) * 100),
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
        $amount             = new Money();
        $member             = Member::currentUser();
        $shoppingCartAmount = $member->shoppingCart()->getTaxableAmountGrossWithoutFees(array('SilvercartVoucher'))->getAmount();
        $rebateAmount       = ($shoppingCartAmount / 100 * $this->valueInPercent);
        $rebate             = new Money();
        $rebate->setAmount($rebateAmount);

        $silvercartVoucherShoppingCartPosition = SilvercartVoucherShoppingCartPosition::get($member->shoppingCart()->ID, $this->ID);

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

        return $fields;
    }
}