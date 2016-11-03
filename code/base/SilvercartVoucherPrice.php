<?php
/**
 * Copyright 2012 pixeltricks GmbH
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
 *
 * @package Silvercart
 * @subpackage SilvercartMarketingVouchers
 */

/**
 * A voucher price class.
 *
 * @package Silvercart
 * @subpackage SilvercartMarketingVouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2012 pixeltricks GmbH
 * @since 19.07.2012
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartVoucherPrice extends DataObject {

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
     * Rendered remove SilvercartVoucherRemoveFromCartForm
     * @var SilvercartVoucherRemoveFromCartForm 
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
     * @var SilvercartTax
     */
    public $Tax = null;

    /**
     * Returns the price sum for this voucher.
     *
     * @param boolean $forSingleProduct Indicates wether the price for the total
     *                                  quantity of products should be returned
     *                                  or for one product only.
     * @param boolean $priceType        'gross' or 'net'. If undefined it'll be automatically chosen.
     *
     * @return SilvercartMoney
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 19.07.2012
     */
    public function getPrice($forSingleProduct = false, $priceType = false) {
        $moneyObj = new SilvercartMoney();
        $moneyObj->setCurrency($this->Currency);

        if ($priceType === false) {
            $priceType = SilvercartConfig::PriceType();
        }

        if ($priceType == 'net') {
            $moneyObj->setAmount($this->PriceNetTotal);
        } else {
            $moneyObj->setAmount($this->PriceTotal);
        }

        return $moneyObj;
    }

    /**
     * Returns the title.
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 19.07.2012
     */
    public function getTitle() {
        return $this->Name;
    }

    /**
     * Returns the short description.
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 19.07.2012
     */
    public function getShortDescription() {
        return $this->ShortDescription;
    }

    /**
     * Returns the long description.
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 19.07.2012
     */
    public function getLongDescription() {
        return $this->LongDescription;
    }

    /**
     * Returns the tax amount.
     *
     * @return float
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 19.07.2012
     */
    public function getTaxAmount() {
        return $this->TaxAmount;
    }
}