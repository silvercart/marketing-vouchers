<?php

namespace SilverCart\Voucher\Admin\Controllers;

use SilverCart\Admin\Controllers\ModelAdmin;
use SilverCart\Voucher\Model\Voucher\AbsoluteRebateVoucher;
use SilverCart\Voucher\Model\Voucher\RelativeRebateVoucher;

/**
 * Voucher administration panel.
 *
 * @package SilverCart
 * @subpackage Voucher\Admin\Controllers
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 14.05.2020
 * @copyright 2020 pixeltricks GmbH
 * @license see license file in modules root directory
 */
class VoucherAdmin extends ModelAdmin
{
    /**
     * The code of the menu under which this admin should be shown.
     * 
     * @var string
     */
    private static $menuCode = 'products';
    /**
     * The section of the menu under which this admin should be grouped.
     * 
     * @var string
     */
    private static $menuSortIndex = 110;
    /**
     * The URL segment
     *
     * @var string
     */
    private static $url_segment = 'silvercart-vouchers';
    /**
     * The menu title
     *
     * @var string
     */
    private static $menu_title = 'Vouchers';
    /**
     * Menu icon
     * 
     * @var string
     */
    private static $menu_icon = null;
    /**
     * Menu icon CSS class
     * 
     * @var string
     */
    private static $menu_icon_class = 'font-icon-block-promo-2';
    /**
     * Managed models
     *
     * @var array
     */
    private static $managed_models = [
        AbsoluteRebateVoucher::class,
        RelativeRebateVoucher::class,
    ];
}