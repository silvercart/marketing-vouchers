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
 * @author Ionut Lipciuc
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
        'value'                  => DBMoney::class,
        'MultipliesWithQuantity' => 'Boolean',
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
            ],
            Tools::field_labels_for(self::class)
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
        if (!$this->isValidForShoppingCartItems($shoppingCart->ShoppingCartPositions())) {
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
            $rebate           = $this->getRebate($shoppingCart);
            $rebateAmount     = $rebate->getAmount();
            $title            = $this->VoucherTitle ? "{$this->VoucherTitle} (Code: {$this->code})" : "{$this->i18n_singular_name()} (Code: {$this->code})";
            $remainingVoucher = $shoppingCart->Member()->Vouchers()->byID($this->ID);
            if ($this->IsRedeemableMultipleTimes
             && $remainingVoucher instanceof Voucher
            ) {
                $shoppingCart->Member()->Vouchers()->remove($remainingVoucher);
                $remainingVoucher = null;
            }
            if ($remainingVoucher instanceof Voucher) {
                $priceGross = DBMoney::create()
                        ->setAmount($remainingVoucher->remainingAmount)
                        ->setCurrency($this->value->getCurrency());
                $originalAmount    = $rebateAmount;
                $originalAmountNet = round($originalAmount / (100 + $this->Tax()->Rate) * 100, 4);
                $originalValue  = DBMoney::create()
                        ->setAmount($originalAmount)
                        ->setCurrency($this->value->getCurrency());
                $originalValueNet = DBMoney::create()
                        ->setAmount($originalAmountNet)
                        ->setCurrency($this->value->getCurrency());
            } else {
                $priceGross = DBMoney::create()
                        ->setAmount($rebateAmount)
                        ->setCurrency($this->value->getCurrency());
            }
            $priceNetAmount = round($priceGross->getAmount() / (100 + $this->Tax()->Rate) * 100, 4);
            $priceNet = DBMoney::create()
                    ->setAmount($priceNetAmount)
                    ->setCurrency(Config::DefaultCurrency());
            if ($remainingVoucher instanceof Voucher) {
                if (Config::PriceType() === Config::PRICE_TYPE_GROSS) {
                    $title = "{$title}<br/>{$this->fieldLabel('OriginalValue')}: {$originalValue->Nice()}"
                           . "<br/>{$this->fieldLabel('RemainingCredit')}: {$priceGross->Nice()}";
                } else {
                    $title = "{$title}<br/>{$this->fieldLabel('OriginalValue')}: {$originalValueNet->Nice()}"
                           . "<br/>{$this->fieldLabel('RemainingCredit')}: {$priceNet->Nice()}";
                }
            }
            // The shopppingcart total may not be below 0
            $excludeShoppingCartPositions[] = $this->ID;
            $shoppingcartTotal              = $shoppingCart->getTaxableAmountWithoutFeesAndCharges([], $excludeShoppingCartPositions);
            if (Config::PriceType() === Config::PRICE_TYPE_GROSS
             && $priceGross->getAmount() > $shoppingcartTotal->getAmount()
            ) {
                $priceGross->setAmount($shoppingcartTotal->getAmount());
                $priceNet->setAmount(round($shoppingcartTotal->getAmount() / (100 + $this->Tax()->Rate) * 100, 4));
            } elseif (Config::PriceType() === Config::PRICE_TYPE_NET
             && $priceNet->getAmount() > $shoppingcartTotal->getAmount()
            ) {
                $priceNet->setAmount($shoppingcartTotal->getAmount());
                $priceGross->setAmount(round($shoppingcartTotal->getAmount() / 100 * (100 + $this->Tax()->Rate), 4));
            }
            $taxAmount = (float) 0.0;
            if ($priceGross->getAmount() > 0) {
                $amount = $priceGross->getAmount();
                if (Config::PriceType() === Config::PRICE_TYPE_GROSS) {
                    $taxAmount = (float) $amount - ($amount / (100 + $this->Tax()->Rate) * 100);
                } else {
                    $taxAmount = (float) ($priceNet->getAmount() * ($this->Tax()->Rate) / 100);
                }
            }
            $priceNet->setAmount($priceNet->getAmount() * -1);
            $priceGross->setAmount($priceGross->getAmount() * -1);
            $position = $this->createVoucherPricePosition($title, $priceGross, $priceNet, $taxAmount);
            $this->extend('updateShoppingCartPosition', $position, $shoppingCart);
            if ($position instanceof VoucherPrice) {
                $positions->push($position);
            }
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
     * @param string  $title      Title of the object
     * @param DBMoney $priceGross gross price object
     * @param DBMoney $priceNet   net price object
     * @param float   $taxAmount  tax amount obj
     *
     * @return VoucherPrice
     *
     * @author Patrick Schneider <pschneider@pixeltricks.de>
     * @since 05.12.2012
     */
    protected function createVoucherPricePosition(string $title, DBMoney $priceGross, DBMoney $priceNet, float $taxAmount) : VoucherPrice
    {
        $priceNetTotal   = $priceNet;
        $voucherPriceObj = VoucherPrice::create();
        $voucherPriceObj->setVoucher($this);
        $voucherPriceObj->ID                     = $this->ID;
        $voucherPriceObj->Name                   = $title;
        $voucherPriceObj->ShortDescription       = $this->Description;
        $voucherPriceObj->Image                  = $this->Image();
        $voucherPriceObj->LongDescription        = $this->Description;
        $voucherPriceObj->Currency               = $priceGross->getCurrency();
        $voucherPriceObj->Price                  = $priceGross->getAmount();
        $voucherPriceObj->PriceFormatted         = $priceGross->Nice();
        $voucherPriceObj->PriceTotal             = $priceGross->getAmount();
        $voucherPriceObj->PriceTotalFormatted    = $priceGross->Nice();
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
     * Returns the rebate amount as a DBMoney object.
     *
     * @param ShoppingCart $shoppingCart The shoppingcart object
     *
     * @return DBMoney
     */
    public function getRebate(ShoppingCart $shoppingCart) : DBMoney
    {
        $rebateBase = 1;
        if ($this->MultipliesWithQuantity
         && $this->isLimitedToRestrictedProducts()
        ) {
            $rebateBase = 0;
            foreach ($this->getAffectedCurrentShoppingCartPositions($shoppingCart) as $position) {
                /* @var $position \SilverCart\Model\Order\ShoppingCartPosition */
                $rebateBase += $position->Quantity;
            }
        }
        return DBMoney::create()
            ->setAmount($this->value->getAmount() * $rebateBase)
            ->setCurrency($this->value->getCurrency());
    }

    /**
     * Returns the affected shopping cart positions.
     *
     * @param ShoppingCart $shoppingCart The shoppingcart object
     *
     * @return ArrayList
     */
    public function getAffectedCurrentShoppingCartPositions(ShoppingCart $shoppingCart) : ArrayList
    {
        if ($this->affectedShoppingCartPositions === null) {
            $this->affectedShoppingCartPositions = ArrayList::create();
            $shoppingCartPositions               = $shoppingCart->ShoppingCartPositions();
            if ($this->isLimitedToRestrictedProducts()) {
                foreach ($this->RestrictToProducts() as $restrictedProduct) {
                    $this->affectedShoppingCartPositions->merge(
                        $shoppingCartPositions->filter(
                            'ProductID', $restrictedProduct->ID
                        )
                    );
                }
                foreach ($this->RestrictToProductGroups() as $restrictedProductGroup) {
                    $this->affectedShoppingCartPositions->merge(
                        $shoppingCartPositions->filter(
                            'Product.ProductGroupID',
                            $restrictedProductGroup->ID
                        )
                    );
                }
            } else {
                $this->affectedShoppingCartPositions = $shoppingCartPositions;
            }
        }
        return $this->affectedShoppingCartPositions;
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
        if ($this->exists()) {
            $fields->addFieldToTab('Root.RestrictToProducts', $fields->dataFieldByName('MultipliesWithQuantity'), 'RestrictToProducts');
        } else {
            $fields->removeByName('MultipliesWithQuantity');
        }
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
            $validator->addRequiredField('value');
        });
        return parent::getCMSValidator();
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
        $remainingAmount = $currentRemainingAmount - ($amountToReduce);
        if ($remainingAmount < 0.0) {
            // this user can't reuse this voucher anymore
            $remainingAmount = 0.0;
        }
        $this->extend('updateRemainingAmount', $remainingAmount, $currentRemainingAmount, $amountToReduce);
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
     */
    public function convert(ShoppingCart $shoppingCart, ShoppingCartPosition $shoppingCartPosition, Voucher $originalVoucher, Member $member) : void
    {
        if (Customer::currentRegisteredCustomer()) {
            // only do this for registered customers
            $amountToReduce  = $shoppingCartPosition->Voucher()->value->getAmount();
            $voucherOnMember = $this->getVoucherOnMember($member, $shoppingCartPosition->VoucherID);
            $pricePositions  = $this->ShoppingCartPositions($shoppingCart, $member);
            if ($pricePositions->exists()) {
                $pricePosition  = $pricePositions->first();
                /* @var $pricePosition VoucherPrice */
                $amountToReduce = $pricePosition->getPrice(true, Config::PRICE_TYPE_GROSS)->getAmount() * -1;
            }
            if (!$voucherOnMember) {
                // this voucher is unused yet by this customer, connect to customer
                $member->Vouchers()->add(
                    $originalVoucher,
                    ['remainingAmount' => $this->doSplitValue($originalVoucher->value->getAmount(), $amountToReduce)]
                );
            } else {
                $this->updateRemainingAmount($member, $originalVoucher, $this->doSplitValue($voucherOnMember->remainingAmount, $amountToReduce));
            }
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
        $voucher = $member->Vouchers()->byID($voucherID);
        $this->extend('updateVoucherOnMember', $voucherID, $voucher, $member);
        return $voucher;
    }

    /**
     * Updates the $remainingAmount for the given $member and $voucher.
     *
     * @param Member  $member          Member
     * @param Voucher $voucher         Voucher
     * @param float   $remainingAmount Remaining amount
     *
     * @return void
     */
    protected function updateRemainingAmount(Member $member, Voucher $voucher, float $remainingAmount) : void
    {
        $voucher2 = $member->Vouchers()->byID($voucher->ID);
        if ($voucher2 === null) {
            $voucherID = $voucher->ID;
            $this->extend('updateVoucherOnMemberContext', $voucherID, $member);
        }
        $member->Vouchers()->add(
            $voucher,
            ['remainingAmount' => $remainingAmount]
        );
    }

    /**
     * can be used to return if a voucher is already fully redeemd,
     * set error message in checkifAllowedInShoppingCart()
     *
     * @param Member $member Member context
     *
     * @return bool
     */
    public function isRedeemable(Member $member = null) : bool
    {
        $isRedeemable = parent::isRedeemable($member);
        if (!$isRedeemable
         && $member instanceof Member
        ) {
            $voucherOnMember = $this->getVoucherOnMember($member, $this->ID);
            if ($voucherOnMember instanceof Voucher
             && $voucherOnMember->remainingAmount > 0
            ) {
                $isRedeemable = true;
            }
        }
        return $isRedeemable;
    }

    /**
     * can be used to return if a voucher is already fully redeemd,
     * set error message in checkifAllowedInShoppingCart()
     *
     * @param Member $member    the member object
     * @param int    $voucherID used voucher code to check for
     *
     * @return bool
     */
    protected function isCompletelyRedeemedAlready(Member $member, int $voucherID) : bool
    {
        $isFullyRedeemedAlready = false;
        if (!$this->IsRedeemableMultipleTimes
         && Customer::currentRegisteredCustomer()
        ) {
            $voucherOnMember = $this->getVoucherOnMember($member, $voucherID);
            if ($voucherOnMember instanceof Voucher
             && $voucherOnMember->remainingAmount == 0.0
            ) {
                $isFullyRedeemedAlready = true;
            }
        }
        return $isFullyRedeemedAlready;
    }
}
