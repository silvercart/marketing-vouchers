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

    public $ID = null;
    public $Name = null;
    public $ShortDescription = null;
    public $LongDescription = null;
    public $Currency = null;
    public $Price = null;
    public $PriceFormatted = null;
    public $PriceTotal = null;
    public $PriceTotalFormatted = null;
    public $PriceNet = null;
    public $PriceNetFormatted = null;
    public $PriceNetTotal = null;
    public $PriceNetTotalFormatted = null;
    public $Quantity = null;
    public $removeFromCartForm = null;
    public $TaxRate = null;
    public $TaxAmount = null;
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