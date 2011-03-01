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
 *
 * @package Silvercart
 * @ignore
 */
SilvercartShoppingCart::registerModule('SilvercartVoucher');
CustomHtmlForm::registerModule('silvercart_vouchers', 50);

// ----------------------------------------------------------------------------
// Register extensions
// ----------------------------------------------------------------------------
Object::add_extension('Member',             'SilvercartVoucherMemberRole');
Object::add_extension('SilvercartProduct',  'SilvercartVoucherProductRole');

// ----------------------------------------------------------------------------
// Extend the product admin
// ----------------------------------------------------------------------------
SilvercartProductAdmin::$managed_models[] = 'SilvercartGiftVoucherProduct';
