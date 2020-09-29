<?php

namespace SilverCart\Voucher\Extensions\Model\Order;

use SilverCart\Voucher\Model\ShoppingCartPosition as VoucherShoppingCartPosition;
use SilverStripe\ORM\DataExtension;

/**
 * Extension for SilverCart ShoppingCart.
 * 
 * @package SilverCart
 * @subpackage Voucher\Extensions\Model\Order
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 29.09.2020
 * @copyright 2020 pixeltricks GmbH
 * @license see license file in modules root directory
 * 
 * @property \SilverCart\Model\Order\ShoppingCart $owner Owner
 */
class ShoppingCartExtension extends DataExtension
{
    /**
     * Has many relations.
     *
     * @var array
     */
    private static $has_many = [
        'VoucherPositions' => VoucherShoppingCartPosition::class . '.ShoppingCart',
    ];
}