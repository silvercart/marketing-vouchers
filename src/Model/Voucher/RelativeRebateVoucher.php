<?php

namespace SilverCart\Voucher\Model\Voucher;

use SilverCart\Admin\Model\Config;
use SilverCart\Dev\Tools;
use SilverCart\Model\Customer\Customer;
use SilverCart\Model\Order\ShoppingCart;
use SilverCart\ORM\FieldType\DBMoney;
use SilverCart\Voucher\Model\ShoppingCartPosition;
use SilverCart\Voucher\Model\Voucher;
use SilverCart\Voucher\Security\VoucherValidator;
use SilverCart\Voucher\View\VoucherPrice;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBHTMLText;
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
     * Is true while @see self::getShoppingCartPositions() is called and not 
     * finished yet.
     * 
     * @var bool
     */
    protected static $loadingShoppingCartPositionsInProgress = false;

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
     */
    public function getShoppingCartPositions(ShoppingCart $shoppingCart, bool $taxable = true, array $excludeShoppingCartPositions = [], bool $createForms = true) : ArrayList
    {
        $positions = ArrayList::create();
        if (self::$loadingShoppingCartPositionsInProgress) {
            return $positions;
        }
        self::$loadingShoppingCartPositionsInProgress = true;
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
            $rebate       = $this->getRebate();
            $rebateAmount = $rebate->getAmount();
            if (Config::Pricetype() === Config::PRICE_TYPE_GROSS) {
                $rebateAmountNet = ((int) $this->Tax()->Rate === 0) ? $rebateAmount : $rebateAmount / (100 + (int) $this->Tax()->Rate) * 100;
            } else {
                $rebateAmountNet = $rebateAmount;
                $rebate->setAmount($rebateAmount * (((int) $this->Tax()->Rate / 100) + 1));
            }
            $rebateNet = DBMoney::create()
                    ->setAmount($rebateAmountNet)
                    ->setCurrency(Config::DefaultCurrency());
            $position = VoucherPrice::create();
            $position->setVoucher($this);
            $position->ID                    = $this->ID;
            $position->Name                  = $this->VoucherTitle ? "{$this->VoucherTitle} (Code: {$this->code})" : "{$this->i18n_singular_name()} (Code: {$this->code})";
            $position->ShortDescription      = $this->renderWith(Voucher::class . '_ShortDescription');
            $position->LongDescription       = $this->Description;
            $position->Image                 = $this->Image();
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
        self::$loadingShoppingCartPositionsInProgress = false;
        return $positions;
    }

    /**
     * Returns the amount to consider in the shopping cart total calculation.
     *
     * @return DBMoney
     */
    public function getShoppingCartTotal() : DBMoney
    {
        $amount       = DBMoney::create();
        $member       = Customer::currentUser();
        $rebate       = $this->getRebate();
        $rebateAmount = $rebate->getAmount();
        $position     = ShoppingCartPosition::getVoucherShoppingCartPosition($member->ShoppingCart()->ID, $this->ID);
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
     * Returns the rebate amount as a DBMoney object.
     * 
     * @return DBMoney
     */
    public function getRebate() : DBMoney
    {
        if ($this->isLimitedToRestrictedProducts()) {
            $rebateBase = 0;
            foreach ($this->getAffectedShoppingCartPositions() as $position) {
                /* @var $position \SilverCart\Model\Order\ShoppingCartPosition */
                $rebateBase += $position->getPrice()->getAmount();
            }
        } else {
            $rebateBase = Customer::currentUser()->ShoppingCart()->getTaxableAmountWithoutFees([Voucher::class])->getAmount();
        }
        return DBMoney::create()
                ->setAmount($rebateBase / 100 * $this->valueInPercent)
                ->setCurrency(Config::DefaultCurrency());
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
     * Returns the custom VoucherValidator to use for CMS field validation.
     * 
     * @return VoucherValidator
     */
    public function getCMSValidator() : VoucherValidator
    {
        $this->beforeUpdateCMSValidator(function(VoucherValidator $validator) {
            $validator->addRequiredField('valueInPercent');
        });
        return parent::getCMSValidator();
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