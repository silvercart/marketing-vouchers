<?php
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
