<?php

namespace SilverCart\Voucher\Forms;

use SilverCart\Forms\CustomForm;
use SilverCart\Model\Customer\Customer;
use SilverCart\Voucher\Model\Voucher;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;

/**
 * This form provides input fields for the voucher code.
 * The user can redeem a voucher with this form.
 *
 * @package SilverCart
 * @subpackage Voucher\Forms
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 14.05.2020
 * @copyright 2020 pixeltricks GmbH
 * @license see license file in modules root directory
 */
class AddVoucherCodeForm extends CustomForm
{
    /**
     * List of required fields.
     *
     * @var array
     */
    private static $requiredFields = [
        'VoucherCode' => [
            'isFilledIn'     => true,
            'isValidVoucher' => true,
        ],
    ];

    /**
     * Returns the static form fields.
     * 
     * @return array
     */
    public function getCustomFields()
    {
        $this->beforeUpdateCustomFields(function (array &$fields) {
            $voucher = Voucher::singleton();
            $fields  = array_merge($fields,[
                TextField::create('VoucherCode', $voucher->fieldLabel('code'), $this->getController()->getRequest()->postVar('VoucherCode'))
                    ->setAttribute('placeholder', $voucher->fieldLabel('code'))
                    ->setAttribute('formnovalidate', 'formnovalidate'),
            ]);
        });
        return parent::getCustomFields();
    }
    
    /**
     * Returns the form actions.
     * 
     * @return array
     */
    public function getCustomActions() : array
    {
        $this->beforeUpdateCustomActions(function (array &$actions) {
            $actions += [
                FormAction::create('submit', Voucher::singleton()->fieldLabel('Redeem'))
                    ->setUseButtonTag(true)->addExtraClass('btn-primary')
            ];
        });
        return parent::getCustomActions();
    }

    /**
     * This method will be call if there are no validation error
     *
     * @param array      $data Submitted data
     * @param CustomForm $form Form object
     *
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 13.11.2017
     */
    public function doSubmit($data, CustomForm $form) : void
    {
        $voucherCode  = Convert::raw2sql($data['VoucherCode']);
        $voucher      = Voucher::get()->filter('code', $voucherCode)->first();
        $member       = Customer::currentUser();
        $shoppingCart = $member->ShoppingCart();
        if ($voucher instanceof Voucher
         && $voucher->exists()
        ) {
            $status = $voucher->checkifAllowedInShoppingCart($voucher, $member, $shoppingCart);
            if ($status['error']) {
                $errorMessage = '';
                foreach ($status['messages'] as $message) {
                    $errorMessage .= "<p>{$message}</p>";
                }
                $this->setErrorMessage($errorMessage);
            } else {
                $voucher->redeem($member, 'manuallyRedeemed');
                $this->getController()->redirect($this->getController()->Link());
            }
        } else {
            $isValidVoucherCode = false;
            $this->owner->extend('updateDoSubmitOnFail', $voucherCode, $isValidVoucherCode);
            if (!$isValidVoucherCode) {
                $this->setErrorMessage(Voucher::singleton()->fieldLabel('ErrorCodeNotValid'));
            }
        }
    }
}