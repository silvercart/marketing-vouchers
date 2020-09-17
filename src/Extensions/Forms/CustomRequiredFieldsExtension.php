<?php

namespace SilverCart\Voucher\Extensions\Forms;

use SilverCart\Model\Customer\Customer;
use SilverCart\Voucher\Model\Voucher;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FormField;

/**
 * Extension for SilverCart CustomRequiredFields.
 * 
 * @package SilverCart
 * @subpackage Voucher\Extensions\Forms
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 01.01.2020
 * @copyright 2020 pixeltricks GmbH
 * @license see license file in modules root directory
 * 
 * @property \SilverCart\Forms\CustomRequiredFields $owner Owner
 */
class CustomRequiredFieldsExtension extends Extension
{
    /**
     * Checks if a field is empty and if this result is expected
     *
     * @param FormField $formField      Form field
     * @param string    $value          Value to check
     * @param bool      $expectedResult The expected result
     *
     * @return array
     */
    public function isValidVoucher(FormField $formField, string $value, bool $expectedResult) : array
    {
        $isValidVoucher = false;
        $errorMessage   = '';
        $voucherCode    = $value;
        $voucher        = Voucher::get()->filter('code', $voucherCode)->first();
        $member         = Customer::currentUser();
        $shoppingCart   = $member->ShoppingCart();
        if ($voucher instanceof Voucher
         && $voucher->exists()
        ) {
            $status = $voucher->checkifAllowedInShoppingCart($voucher, $member, $shoppingCart);
            if ($status['error']) {
                foreach ($status['messages'] as $message) {
                    $errorMessage .= "<p>{$message}</p>";
                }
            } else {
                $isValidVoucher = true;
            }
        } else {
            $this->owner->extend('updateIsValidVoucherOnFail', $voucherCode, $isValidVoucher);
            if (!$isValidVoucher) {
                $errorMessage = Voucher::singleton()->fieldLabel('ErrorCodeNotValid');
            }
        }
        return [
            'error'        => !($isValidVoucher === $expectedResult),
            'errorMessage' => $errorMessage
        ];
    }
}