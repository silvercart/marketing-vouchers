<?php
/**
 * Copyright 2011 pixeltricks GmbH
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
 */

/**
 * Voucher administration panel.
 *
 * @package Silvercart
 * @subpackage Vouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 21.01.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartVoucherAdmin extends SilvercartModelAdmin {

    /**
     * The code of the menu under which this admin should be shown.
     * 
     * @var string
     */
    public static $menuCode = 'products';

    /**
     * The URL segment
     *
     * @var string
     */
    public static $url_segment = 'silvercart-vouchers';

    /**
     * The menu title
     *
     * @var string
     */
    public static $menu_title  = 'Vouchers';
    
    /**
     * Managed models
     *
     * @var array
     */
    public static $managed_models = array(
        'SilvercartAbsoluteRebateVoucher',
        'SilvercartRelativeRebateVoucher'
    );
    
    /**
     * Set the translations.
     * 
     * @return void
     * 
     * @author Sascha Koehler <skoehler@pixeltricks.de>, Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.08.2012
     */
    public function __construct() {
        self::$menu_title = _t('SilvercartVoucherAdmin.TITLE');
        parent::__construct();
    }
}