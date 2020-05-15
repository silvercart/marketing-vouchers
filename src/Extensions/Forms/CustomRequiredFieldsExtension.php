<?php

namespace SilverCart\Voucher\Extensions\Forms;

use SilverCart\Model\Customer\Customer;
use SilverCart\Voucher\Model\Voucher;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FormField;

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
            $errorMessage = Voucher::singleton()->fieldLabel('ErrorCodeNotValid');
        }
        return [
            'error'        => !($isValidVoucher === $expectedResult),
            'errorMessage' => $errorMessage
        ];
    }
}