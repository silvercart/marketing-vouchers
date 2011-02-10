<?php
ShoppingCart::registerModule('SilvercartVoucher');
CustomHtmlForm::registerModule('silvercart_vouchers', 50);

// ----------------------------------------------------------------------------
// Register extensions
// ----------------------------------------------------------------------------
Object::add_extension('Member',  'SilvercartVoucherCustomerRole');
Object::add_extension('Article', 'SilvercartVoucherArticleRole');

// ----------------------------------------------------------------------------
// Extend the article admin
// ----------------------------------------------------------------------------
ArticleAdmin::$managed_models[] = 'SilvercartGiftVoucherArticle';