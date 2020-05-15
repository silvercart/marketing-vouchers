<?php

namespace SilverCart\Voucher\Extensions\Model\Pages;

use SilverCart\Model\Customer\Customer;
use SilverCart\Voucher\Forms\AddVoucherCodeForm;
use SilverCart\Voucher\Model\Voucher;
use SilverStripe\Core\Extension;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;

/**
 * Extension for SilverCart CartPageController.
 *
 * @package SilverCart
 * @subpackage Voucher\Extensions\Model\Pages
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 14.05.2020
 * @copyright 2020 pixeltricks GmbH
 * @license see license file in modules root directory
 * 
 * @param \SilverCart\Model\Pages\CartPageController $owner Owner
 */
class CartPageControllerExtension extends Extension
{
    /**
     * Allowed actions
     *
     * @var array
     */
    private static $allowed_actions = [
        'AddVoucherCodeForm',
        'removeVoucher',
    ];
    
    /**
     * Returns the AddVoucherCodeForm.
     * 
     * @param HTTPRequest $request Request
     * 
     * @return AddVoucherCodeForm
     */
    public function AddVoucherCodeForm() : AddVoucherCodeForm
    {
        return AddVoucherCodeForm::create($this->owner);
    }
    
    public function removeVoucher(HTTPRequest $request) : HTTPResponse
    {
        $voucher = Voucher::get()->byID((int) $request->param('ID'));
        if ($voucher instanceof Voucher) {
            $member = Customer::currentUser();
            $voucher->removeFromShoppingCart($member, 'manuallyRemoved');
        }
        return HTTPResponse::create($this->owner->render(), 200);
    }
}