<?php
ShoppingCart::registerModule('SilvercartVoucher');

// ----------------------------------------------------------------------------
// Register extensions
// ----------------------------------------------------------------------------
Object::add_extension('Member',               'SilvercartVoucherCustomerRole');