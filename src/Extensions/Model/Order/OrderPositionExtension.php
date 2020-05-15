<?php

namespace SilverCart\Voucher\Extensions\Model\Order;

use SilverCart\ORM\FieldType\DBMoney;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;

/**
 * Extends the SilverCart OrderPosition object with voucher specific fields and methods.
 * 
 * @package SilverCart
 * @subpackage Voucher\Extensions\Model\Order
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @since 14.05.2020
 * @copyright 2020 pixeltricks GmbH
 * @license see license file in modules root directory
 */
class OrderPositionExtension extends DataExtension
{
    /**
     * DB attributes.
     *
     * @var array
     */
    private static $db = [
        'VoucherCode'  => 'Text',
        'VoucherValue' => DBMoney::class,
    ];

    /**
     * Returns the voucher codes for this order position.
     *
     * @return ArrayList
     */
    public function getVoucherCodes() : ArrayList
    {
        $voucherCodes = [];
        if (strpos($this->owner->VoucherCode, ',') !== false) {
            $codes = explode(', ', $this->owner->VoucherCode);
            foreach ($codes as $code) {
                $voucherCodes[] = [
                    'code' => $code
                ];
            }
        } else {
            $voucherCodes[] = [
                'code' => $this->owner->VoucherCode
            ];
        }
        return ArrayList::create($voucherCodes);
    }
}