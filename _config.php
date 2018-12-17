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
CustomHtmlForm::registerModule(SilvercartTools::get_module_name(), 50);

// ----------------------------------------------------------------------------
// Register extensions
// ----------------------------------------------------------------------------
SS_Object::add_extension('Member',                                 'SilvercartVoucherMember');
SS_Object::add_extension('SilvercartOrderPosition',                'SilvercartVoucherOrderPosition');
SS_Object::add_extension('SilvercartOrderDetailPage_Controller',   'SilvercartVoucherOrderDetailPage_Controller');

// ----------------------------------------------------------------------------
// Register SilvercartPlugins
// ----------------------------------------------------------------------------
SS_Object::add_extension('SilvercartVoucherRemoveFromCartForm', 'SilvercartPluginObjectExtension');

SilvercartPlugin::registerPluginProvider('SilvercartVoucherRemoveFromCartForm', 'SilvercartVoucherRemoveFromCartFormPluginProvider');

SilvercartLeftAndMainExtension::add_additional_css_file(SilvercartTools::get_module_name() . '/css/SilvercartVoucherAdmin.css');