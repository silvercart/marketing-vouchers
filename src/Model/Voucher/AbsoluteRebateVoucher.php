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
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Member;

/**
 * Extends the voucher class for absolute rebates, i.e. 50,00 Eur.
 *
 * @package SilverCart
 * @subpackage Voucher\Model\Voucher
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 14.05.2020
 * @copyright 2020 pixeltricks GmbH
 * @license see license file in modules root directory
 * 
 * @param DBMoney $value Value
 */
class AbsoluteRebateVoucher extends Voucher
{
    /**
     * Table name
     *
     * @var string
     */
    private static $table_name = 'SilvercartVoucherAbsoluteRebate';
    /**
     * Attributes.
     *
     * @var array
     */
    private static $db = [
        'value' => DBMoney::class,
    ];
    /**
     * IDs of positions that have already been handled
     * 
     * @var array
     */
    public static $alreadyHandledPositionIDs = [];
    /**
     * The actual positions that have already been handled
     * 
     * @var ArrayList[]
     */
    public static $alreadyHandledPositions = [];

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
     * Field labels for display in tables.
     *
     * @param boolean $includerelations A boolean value to indicate if the labels returned include relation fields
     *
     * @return array
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 19.10.2011
     */
    public function fieldLabels($includerelations = true) : array
    {
        return array_merge(
            parent::fieldLabels($includerelations),
            [
                'value' => _t(self::class . '.VALUE', 'value'),
            ]
        );
    }

    /**
     * Returns a SS_List for the display of the voucher positions in the
     * shoppingcart.
     *
     * @param ShoppingCart $shoppingCart                 The shoppingcart object
     * @param bool         $taxable                      Indicates if taxable or nontaxable entries should be returned
     * @param array        $excludeShoppingCartPositions Positions that shall not be counted; can be the ID or the className of the position
     * @param bool         $createForms                  Indicates wether the form objects should be created or not
     *
     * @return SS_List
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>,
     *         Sascha Koehler <skoehler@pixeltricks.de>
     * @since 03.02.2015
     */
    public function getShoppingCartPositions(ShoppingCart $shoppingCart, bool $taxable = true, array $excludeShoppingCartPositions = [], bool $createForms = true) : ArrayList
    {
        $positions = ArrayList::create();
        if (!empty($excludeShoppingCartPositions)
         && (in_array($this->ID, $excludeShoppingCartPositions)
          || in_array($this->class, $excludeShoppingCartPositions))
        ) {
            return $positions;
        }
        $controller = Controller::curr();
        if (!($controller instanceof \PageController)) {
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
            if (in_array($this->ID, self::$alreadyHandledPositionIDs)) {
                return self::$alreadyHandledPositions[$this->ID];
            }
            $title          = "{$this->singular_name()} (Code: {$this->code})";
            $priceNetAmount = round($this->value->getAmount() / (100 + $this->Tax()->Rate) * 100, 4);
            $priceNet = DBMoney::create()
                    ->setAmount($priceNetAmount)
                    ->setCurrency(Config::DefaultCurrency());
            // The shopppingcart total may not be below 0
            $excludeShoppingCartPositions[] = $this->ID;
            $shoppingcartTotal              = $shoppingCart->getTaxableAmountGrossWithoutFeesAndCharges([], $excludeShoppingCartPositions);
            $originalAmount                 = $this->value->getAmount();
            if ($originalAmount >= $shoppingcartTotal->getAmount()) {
                $originalAmountObj = DBMoney::create()
                        ->setAmount($originalAmount);
                $title .= sprintf(
                    "<br />%s: %s",
                    $this->fieldLabel('OriginalValue'),
                    $originalAmountObj->Nice()
                );
                $member = Customer::currentRegisteredCustomer();
                if ($member
                 && !is_null($member->Vouchers()->find('ID', $this->ID))
                ) {
                    $voucherOnMember = $member->Vouchers()->find('ID', $this->ID);
                    $this->value->setAmount((float) $voucherOnMember->remainingAmount);
                    $restAmountObj = DBMoney::create()
                            ->setAmount($voucherOnMember->remainingAmount);
                    $title .= sprintf(
                        "<br />%s: %s",
                        $this->fieldLabel('RemainingCredit'),
                        $restAmountObj->Nice()
                    );
                } else {
                    $this->value->setAmount($shoppingcartTotal->getAmount());
                    $priceNet->setAmount($shoppingcartTotal->getAmount());
                }
            }
            $taxAmount = (float) 0.0;
            if ($this->value->getAmount() > 0) {
                $amount = $this->value->getAmount();
                if (Config::PriceType() == 'gross') {
                    $taxAmount = (float) $amount - ($amount / (100 + $this->Tax()->Rate) * 100);
                } else {
                    $taxAmount = (float) ($priceNet->getAmount() * ($this->Tax()->Rate) / 100);
                }
            }
            $priceNet->setAmount($priceNet->getAmount() * -1);
            $this->value->setAmount($this->value->getAmount() * -1);
            $position = $this->createVoucherPricePosition($title, $priceNet, $taxAmount);
            $positions->push($position);
            if (!in_array($this->ID, self::$alreadyHandledPositionIDs)) {
                self::$alreadyHandledPositionIDs[] = $this->ID;
            }
            self::$alreadyHandledPositions[$this->ID] = $positions;
        }
        return $positions;
    }
    
    /**
     * creates a VoucherPrice and returns it
     * 
     * @param string  $title     Title of the object
     * @param DBMoney $priceNet  net price object
     * @param float   $taxAmount tax amount obj
     * 
     * @return VoucherPrice
     * 
     * @author Patrick Schneider <pschneider@pixeltricks.de>
     * @since 05.12.2012
     */
    protected function createVoucherPricePosition(string $title, DBMoney $priceNet, float $taxAmount) : VoucherPrice
    {
        $priceNetTotal   = $priceNet;
        $voucherPriceObj = VoucherPrice::create();
        $voucherPriceObj->ID                     = $this->ID;
        $voucherPriceObj->Name                   = $title;
        $voucherPriceObj->ShortDescription       = $this->code;
        $voucherPriceObj->LongDescription        = $this->code;
        $voucherPriceObj->Currency               = $this->value->getCurrency();
        $voucherPriceObj->Price                  = $this->value->getAmount();
        $voucherPriceObj->PriceFormatted         = $this->value->Nice();
        $voucherPriceObj->PriceTotal             = $this->value->getAmount();
        $voucherPriceObj->PriceTotalFormatted    = $this->value->Nice();
        $voucherPriceObj->PriceNet               = $priceNet->getAmount();
        $voucherPriceObj->PriceNetFormatted      = $priceNet->Nice();
        $voucherPriceObj->PriceNetTotal          = $priceNetTotal->getAmount();
        $voucherPriceObj->PriceNetTotalFormatted = $priceNetTotal->Nice();
        $voucherPriceObj->Quantity               = 1;
        $voucherPriceObj->TaxRate                = $this->Tax()->Rate;
        $voucherPriceObj->TaxAmount              = -$taxAmount;
        $voucherPriceObj->Tax                    = $this->Tax();
        $voucherPriceObj->ProductNumber          = $this->ProductNumber;
        $voucherPriceObj->removeFromCartForm     = $this->renderWith(Voucher::class . '_remove');
        return $voucherPriceObj;
    }

    /**
     * Returns the amount to consider in the shopping cart total calculation.
     *
     * @return DBMoney
     */
    public function getShoppingCartTotal() : DBMoney
    {
        $amount   = DBMoney::create();
        $member   = Customer::currentUser();
        $position = ShoppingCartPosition::getVoucherShoppingCartPosition($member->ShoppingCart()->ID, $this->ID);

        if ($position instanceof ShoppingCartPosition
         && $position->implicatePosition
        ) {
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
     * splits a value of a voucher to make sure a voucher can be used until it
     * has a value of 0
     * 
     * @param float $currentRemainingAmount current remaining amount for customer <->member
     * @param float $amountToReduce         amount to reduce
     *
     * @return float
     *
     * @author Patrick Schneider <pschneider@pixeltricks.de>
     * @since 03.12.2012
     */
    protected function doSplitValue(float $currentRemainingAmount, float $amountToReduce) : float
    {
        $remainingAmount = $currentRemainingAmount - ($amountToReduce * -1);
        if ($remainingAmount < 0.0) {
            // this user can't reuse this voucher anymore
            $remainingAmount = 0.0;
        }
        return $remainingAmount;
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
     *
     * @author Patrick Schneider <pschneider@pixeltricks.de>
     * @since 03.12.2012
     */
    public function convert(ShoppingCart $shoppingCart, ShoppingCartPosition $shoppingCartPosition, Voucher $originalVoucher, Member $member) : void
    {
        if (Customer::currentRegisteredCustomer()) {
            // only do this for registered customers
            $currentRemainingAmount = null;
            $amountToReduce         = $shoppingCartPosition->Voucher()->value->getAmount();
            $voucherOnMember        = $member->Vouchers()->find('ID', $shoppingCartPosition->VoucherID);
            if (!$voucherOnMember) {
                // this voucher is unused yet by this customer, connect to customer
                $member->Vouchers()->add($originalVoucher);
                $currentRemainingAmount = $originalVoucher->value->getAmount();
            } else {
                $currentRemainingAmount = $voucherOnMember->remainingAmount;
            }
            $newRemainingAmount = $this->doSplitValue($currentRemainingAmount, $amountToReduce);
            $member->Vouchers()->add(
                $originalVoucher, 
                ['remainingAmount' => $newRemainingAmount]
            );
        }
    }
    
    /**
     * returns the relation object for given member and voucherID
     * null if it does not exist
     * 
     * @param Member $member    member object to search on
     * @param int    $voucherID voucherID to search for
     * 
     * @return Voucher|NULL
     */
    protected function getVoucherOnMember(Member $member, int $voucherID) : ?Voucher
    {
        return $member->Vouchers()->byID($voucherID);
    }
    
    /**
     * can be used to return if a voucher is already fully redeemd,
     * set error message in checkifAllowedInShoppingCart()
     * 
     * @param Member $member    the member object
     * @param int    $voucherID used voucher code to check for
     * 
     * @return bool
     * 
     * @author Patrick Schneider <pschneider@pixeltricks.de>
     * @since 06.12.2012
     */
    protected function isCompletelyRedeemedAlready(Member $member, int $voucherID) : bool
    {
        $isFullyRedeemedAlready = false;
        if (Customer::currentRegisteredCustomer()) {
            $voucherOnMember = $member->Vouchers()->byID($voucherID);
            if ($voucherOnMember instanceof Voucher
             && $voucherOnMember->remainingAmount == 0.0
            ) {
                $isFullyRedeemedAlready = true;
            }
        }
        return $isFullyRedeemedAlready;
    }
}