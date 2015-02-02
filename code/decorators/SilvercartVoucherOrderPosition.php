<?php
/**
 * Copyright 2015 pixeltricks GmbH
 *
 * This file is part of SilverCart.
 * 
 * @package Silvercart
 * @package Vouchers
 */

/**
 * Extends the SilvercartOrderPosition object with voucher specific fields and methods.
 *
 * @package Silvercart
 * @package Vouchers
 * @author Sebastian Diel <sdiel@pixeltricks.de>,
 *         Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2015 pixeltricks GmbH
 * @since 02.02.2015
 * @license see license file in modules root directory
 */
class SilvercartVoucherOrderPosition extends DataExtension {

    /**
     * DB attributes.
     *
     * @var array
     */
    private static $db = array(
        'SilvercartVoucherCode'  => 'Text',
        'SilvercartVoucherValue' => 'Money',
    );

    /**
     * Returns the voucher codes for this order position.
     *
     * @return DataObjectSet
     */
    public function getVoucherCodes() {
        $voucherCodes = array();

        if (strpos($this->owner->SilvercartVoucherCode, ',') !== false) {
            $codes = explode(', ', $this->owner->SilvercartVoucherCode);

            foreach ($codes as $code) {
                $voucherCodes[] = array(
                    'code' => $code
                );
            }
        } else {
            $voucherCodes[] = array(
                'code' => $this->owner->SilvercartVoucherCode
            );
        }

        return new DataObjectSet($voucherCodes);
    }
}
