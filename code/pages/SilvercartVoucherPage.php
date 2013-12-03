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
 * Extension for default page controller.
 *
 * @package Silvercart
 * @package Vouchers
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 03.12.2013
 * @copyright 2013 pixeltricks GmbH
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * 
 */
class SilvercartVoucherPage_Controller extends DataObjectDecorator {
    
    /**
     * Sets an URL param triggered voucher generator on before init.
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 03.12.2013
     */
    public function onBeforeInit() {
        SilvercartAutoVoucherGenerator::set_by_current_url_params();
    }
    
}