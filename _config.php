<?php
SilvercartShoppingCart::registerModule('SilvercartVoucher');
CustomHtmlForm::registerModule('silvercart_vouchers', 50);

// ----------------------------------------------------------------------------
// Register extensions
// ----------------------------------------------------------------------------
Object::add_extension('Member',             'SilvercartVoucherCustomerRole');
Object::add_extension('SilvercartProduct',  'SilvercartVoucherArticleRole');

// ----------------------------------------------------------------------------
// Extend the article admin
// ----------------------------------------------------------------------------
SilvercartProductAdmin::$managed_models[] = 'SilvercartGiftVoucherArticle';
