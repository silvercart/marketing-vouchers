<?php
/**
 * Copyright 2013 pixeltricks GmbH
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
 * Voucher generator administration panel.
 *
 * @package Silvercart
 * @subpackage Vouchers
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 21.11.2013
 * @copyright 2013 pixeltricks GmbH
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartVoucherGeneratorAdmin extends ModelAdmin {

    /**
     * The code of the menu under which this admin should be shown.
     * 
     * @var string
     */
    public static $menuCode = 'products';

    /**
     * The section of the menu under which this admin should be grouped.
     * 
     * @var string
     */
    public static $menuSortIndex = 151;

    /**
     * The URL segment
     *
     * @var string
     */
    public static $url_segment = 'silvercart-voucher-generator';

    /**
     * The menu title
     *
     * @var string
     */
    public static $menu_title  = 'Vouchers Generator';
    
    /**
     * Managed models
     *
     * @var array
     */
    public static $managed_models = array(
        'SilvercartAutoVoucherGenerator',
    );
    
    /**
     * Set the translations.
     * 
     * @return void
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.08.2012
     */
    public function __construct() {
        self::$menu_title = _t('SilvercartVoucherGeneratorAdmin.TITLE');
        parent::__construct();
    }
}