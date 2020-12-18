<?php

namespace SilverCart\Voucher\View;

use SilverCart\Admin\Model\Config;
use SilverCart\ORM\FieldType\DBMoney;
use SilverCart\Voucher\Model\Voucher;
use SilverStripe\View\ViewableData;

/**
 * A voucher price class.
 *
 * @package SilverCart
 * @subpackage Voucher\View
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 14.05.2020
 * @copyright 2020 pixeltricks GmbH
 * @license see license file in modules root directory
 */
class VoucherPrice extends ViewableData
{
    /**
     * ID of the voucher
     * @var Int
     */
    public $ID = null;
    /**
     * Name of the voucher
     * @var String 
     */
    public $Name = null;
    /**
     * Short description of the voucher
     * @var String
     */
    public $ShortDescription = null;
    /**
     * Long description of the voucher
     * @var String
     */
    public $LongDescription = null;
    /**
     * Currency of the voucher
     * @var String 
     */
    public $Currency = null;
    /**
     * Price of the voucher
     * @var Float 
     */
    public $Price = null;
    /**
     * Formatted price of the voucher
     * @var String 
     */
    public $PriceFormatted = null;
    /**
     * Total price of the voucher
     * @var Float 
     */
    public $PriceTotal = null;
    /**
     * Formatted total price of the voucher
     * @var String 
     */
    public $PriceTotalFormatted = null;
    /**
     * Net price of the voucher
     * @var Float 
     */
    public $PriceNet = null;
    /**
     * Formatted net price of the voucher
     * @var String
     */
    public $PriceNetFormatted = null;
    /**
     * Total net price of the voucher
     * @var Float 
     */
    public $PriceNetTotal = null;
    /**
     * Formatted total net price of the voucher
     * @var String 
     */
    public $PriceNetTotalFormatted = null;
    /**
     * Quantity
     * @var Int 
     */
    public $Quantity = null;
    /**
     * Rendered remove VoucherRemoveFromCartForm
     * @var \SilverStripe\ORM\FieldType\DBHTMLText 
     */
    public $removeFromCartForm = null;
    /**
     * Tax rate of the voucher
     * @var Float
     */
    public $TaxRate = null;
    /**
     * Tax amount of the voucher
     * @var Float
     */
    public $TaxAmount = null;
    /**
     *Tax object of the voucher
     * @var \SilverCart\Model\Product\Tax
     */
    public $Tax = null;
    /**
     * Voucher
     * 
     * @var \SilverCart\Voucher\Model\Voucher
     */
    public $Voucher = null;

    /**
     * Sets the price gross amount and re-calculates the price net amount.
     * 
     * @param float $priceGrossAmount New price gross
     * 
     * @return void
     */
    public function setPriceGrossAmount(float $priceGrossAmount) : void
    {
        $priceNetAmount      = round($priceGrossAmount / (100 + $this->TaxRate) * 100, 4);
        $this->Price         = $priceGrossAmount;
        $this->PriceTotal    = $priceGrossAmount;
        $this->PriceNet      = $priceNetAmount;
        $this->PriceNetTotal = $priceNetAmount;
    }
    
    /**
     * Returns the price sum for this voucher.
     *
     * @param bool   $forSingleProduct Indicates wether the price for the total
     *                                 quantity of products should be returned
     *                                 or for one product only.
     * @param string $priceType       'gross' or 'net'. If undefined it'll be automatically chosen.
     *
     * @return DBMoney
     */
    public function getPrice(bool $forSingleProduct = false, string $priceType = null) : DBMoney
    {
        $moneyObj = DBMoney::create();
        $moneyObj->setCurrency($this->Currency);
        if ($priceType === null) {
            $priceType = Config::PriceType();
        }
        if ($priceType === 'net') {
            $moneyObj->setAmount($this->PriceNetTotal);
        } else {
            $moneyObj->setAmount($this->PriceTotal);
        }
        return $moneyObj;
    }

    /**
     * Returns the title.
     *
     * @return string|NULL
     */
    public function getTitle() : ?string
    {
        return $this->Name;
    }

    /**
     * Returns the short description.
     *
     * @return string|NULL
     */
    public function getShortDescription() : ?string
    {
        return $this->ShortDescription;
    }

    /**
     * Returns the long description.
     *
     * @return string|NULL
     */
    public function getLongDescription() : ?string
    {
        return $this->LongDescription;
    }

    /**
     * Returns the tax amount.
     *
     * @return float
     */
    public function getTaxAmount() : float
    {
        return (float) $this->TaxAmount;
    }

    /**
     * Returns the quantity according to the SilverCart Product quantity type
     * setting.
     *
     * @return int
     */
    public function getTypeSafeQuantity() : int
    {
       return (int) $this->Quantity;
    }
    
    /**
     * Sets the voucher.
     * 
     * @param Voucher $voucher Voucher
     * 
     * @return \SilverCart\Voucher\View\VoucherPrice
     */
    public function setVoucher(Voucher $voucher) : VoucherPrice
    {
        $this->Voucher = $voucher;
        return $this;
    }
    
    /**
     * Returns the voucher.
     * 
     * @return Voucher|null
     */
    public function getVoucher() : ?Voucher
    {
        return $this->Voucher;
    }
}