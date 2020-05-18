<?php

use SilverCart\Model\Order\ShoppingCart;
use SilverCart\Voucher\Model\Voucher;

if (Voucher::config()->enable_voucher_module) {
    ShoppingCart::registerModule(Voucher::class);
}