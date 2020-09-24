<?php

namespace SilverCart\Voucher\Model\Voucher;

use SilverCart\Admin\Model\Config;
use SilverCart\Dev\Tools;
use SilverCart\Model\Customer\Customer;
use SilverCart\Model\Order\ShoppingCart;
use SilverCart\ORM\FieldType\DBMoney;
use SilverCart\Voucher\Model\ShoppingCartPosition;
use SilverCart\Voucher\Model\Voucher;
use SilverCart\Voucher\View\VoucherPrice;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Member;

/**
 * Extends the voucher class for relative rebates that are subtracted from
 * the shoppingcart total sum (e.g. 10%).
 *
 * @package SilverCart
 * @subpackage Voucher\Model\Voucher
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 14.05.2020
 * @copyright 2020 pixeltricks GmbH
 * @license see license file in modules root directory
 * 
 * @param int $valueInPercent Value in percent
 */
class RelativeRebateVoucher extends Voucher
{
    /**
     * Table name
     *
     * @var string
     */
    private static $table_name = 'SilvercartVoucherRelativeRebate';
    /**
     * Attributes.
     *
     * @var array
     */
    private static $db = [
        'valueInPercent' => 'Int'
    ];
    /**
     * Summary fields for the model admin table.
     *
     * @var array
     */
    private static $summary_fields = [
        'code',
        'valueInPercent',
    ];

    /**
     * Returns the translated plural name of the object. If no translation exists
     * the class name will be returned.
     *
     * @return string
     */
    public function plural_name() : string
    {
        return Tools::plural_name_for($this);
    }

    /**
     * Returns the translated singular name of the object. If no translation exists
     * the class name will be returned.
     *
     * @return string
     */
    public function singular_name() : string
    {
        return Tools::singular_name_for($this);
    }

    /**
     * Summary field labels for the model admin.
     *
     * @param bool $includerelations a boolean value to indicate if the labels returned include relation fields
     * 
     * @return array
     */
    public function fieldLabels($includerelations = true) : array
    {
        return array_merge(
                parent::fieldLabels($includerelations),
                [
                    'valueInPercent' => _t(Voucher::class . '.VALUE_IN_PERCENT', 'Rebate value in percent')
                ]
        );
    }

    /**
     * Returns a ArrayList for the display of the voucher positions in the
     * shoppingcart.
     *
     * @param ShoppingCart $shoppingCart                 The shoppingcart object
     * @param bool         $taxable                      Indicates if taxable or nontaxable entries should be returned
     * @param array        $excludeShoppingCartPositions Positions that shall not be counted
     * @param bool         $createForms                  Indicates wether the form objects should be created or not
     *
     * @return ArrayList
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>,
     *         Sascha Koehler <skoehler@pixeltricks.de>
     * @since 03.02.2015
     */
    public function getShoppingCartPositions(ShoppingCart $shoppingCart, bool $taxable = true, array $excludeShoppingCartPositions = [], bool $createForms = true) : ArrayList
    {
        $positions = ArrayList::create();
        if (in_array($this->ID, $excludeShoppingCartPositions)) {
            return $positions;
        }
        $tax = $this->Tax();
        if ((!$taxable
          && !$tax)
         || (!$taxable
          && $tax->Rate == 0)
         || ($taxable
          && $tax
          && $tax->Rate > 0)
        ) {
            $shoppingCartAmount = $shoppingCart->getTaxableAmountWithoutFees([Voucher::class])->getAmount();
            $rebateAmount       = round(($shoppingCartAmount / 100 * $this->valueInPercent), 2);
            $rebateAmountNet    = ((int) $this->Tax()->Rate === 0) ? $rebateAmount : $rebateAmount / (100 + (int) $this->Tax()->Rate) * 100;
            $rebate             = DBMoney::create()
                    ->setAmount($rebateAmount)
                    ->setCurrency(Config::DefaultCurrency());
            $rebateNet          = DBMoney::create()
                    ->setAmount($rebateAmountNet)
                    ->setCurrency(Config::DefaultCurrency());
            $position = VoucherPrice::create();
            $position->ID                    = $this->ID;
            $position->Name                  = "{$this->singular_name()} (Code: {$this->code})";
            $position->ShortDescription      = $this->code;
            $position->LongDescription       = $this->code;
            $position->Currency              = Config::DefaultCurrency();
            $position->Price                 = $rebateAmount * -1;
            $position->PriceFormatted        = '-' . $rebate->Nice();
            $position->PriceTotal            = $rebateAmount * -1;
            $position->PriceTotalFormatted   = '-' . $rebate->Nice();
            $position->PriceNet              = $rebateAmountNet * -1;
            $position->PriceNetFormatted     = '-' . $rebateNet->Nice();
            $position->PriceNetTotal         = $rebateAmountNet * -1;
            $position->PriceNetTotalFormatted= '-' . $rebateNet->Nice();
            $position->Quantity              = 1;
            $position->removeFromCartForm    = $this->renderWith(Voucher::class . '_remove');;
            $position->TaxRate               = $this->Tax()->Rate;
            $position->TaxAmount             = ($rebateAmount - ($rebateAmount / (100 + $this->Tax()->Rate) * 100)) * -1;
            $position->Tax                   = $this->Tax();
            $position->ProductNumber         = $this->ProductNumber;
            $this->extend('updateShoppingCartPosition', $position, $shoppingCart);
            if ($position instanceof VoucherPrice) {
                $positions->push($position);
            }
        }
        return $positions;
    }

    /**
     * Returns the amount to consider in the shopping cart total calculation.
     *
     * @return DBMoney
     */
    public function getShoppingCartTotal() : DBMoney
    {
        $amount             = DBMoney::create();
        $member             = Customer::currentUser();
        $shoppingCartAmount = $member->ShoppingCart()->getTaxableAmountWithoutFees([Voucher::class])->getAmount();
        $rebateAmount       = ($shoppingCartAmount / 100 * $this->valueInPercent);
        $rebate             = DBMoney::create()
                ->setAmount($rebateAmount)
                ->setCurrency(Config::DefaultCurrency());
        $position           = ShoppingCartPosition::getVoucherShoppingCartPosition($member->ShoppingCart()->ID, $this->ID);
        if ($position instanceof ShoppingCartPosition
         && $position->implicatePosition
        ) {
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
     * @return FieldList
     */
    public function getCMSFields() : FieldList
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('quantityRedeemed');
        $fields->addFieldToTab('Root.Main', LiteralField::create('quantityRedeemed', "<br />{$this->fieldLabel('RedeemedVouchers')}" . ($this->quantityRedeemed ? $this->quantityRedeemed : '0')));
        return $fields;
    }

    /**
     * This method gets called when converting the shoppingcart positions to
     * order positions.
     *
     * @param ShoppingCart         $shoppingCart         the shoppingcart object
     * @param ShoppingCartPosition $shoppingCartPosition position of the shoppingcart which contains the voucher
     * @param Voucher              $originalVoucher      the original voucher
     * @param Member               $member               member object
     *
     * @return void
     */
    public function convert(ShoppingCart $shoppingCart, ShoppingCartPosition $shoppingCartPosition, Voucher $originalVoucher, Member $member) : void
    {
        if (Customer::currentRegisteredCustomer()) {
            // only do this for registered customers
            $voucherOnMember = $member->Vouchers()->find('ID', $shoppingCartPosition->VoucherID);
            if (!$voucherOnMember) {
                $member->Vouchers()->add($originalVoucher);
            }
        }
    }
}