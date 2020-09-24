<?php

namespace SilverCart\Voucher\Extensions\Model\Order;

use SilverCart\Model\Order\OrderPosition;
use SilverCart\Voucher\Model\Voucher;
use SilverCart\Voucher\View\VoucherPrice;
use SilverStripe\ORM\DataExtension;

/**
 * Extension for SilverCart Order.
 * 
 * @package SilverCart
 * @subpackage Voucher\Extensions\Model\Order
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 24.09.2020
 * @copyright 2020 pixeltricks GmbH
 * @license see license file in modules root directory
 * 
 * @property \SilverCart\Model\Order\Order $owner Owner
 */
class OrderExtension extends DataExtension
{
    /**
     * Adds the voucher code to a voucher order position if necessary.
     * 
     * @param object        $modulePosition Module injected position
     * @param OrderPosition $orderPosition  Converted order position
     * @param string        $moduleName     Source module name
     * 
     * @return void
     */
    public function onBeforeConvertSingleModulePositionToOrderPosition(object $modulePosition, OrderPosition $orderPosition, string $moduleName) : void
    {
        if ($modulePosition instanceof VoucherPrice) {
            $voucher = Voucher::get()->byID($modulePosition->ID);
            if ($voucher instanceof Voucher) {
                $orderPosition->VoucherCode = $voucher->code;
            }
        }
    }
}