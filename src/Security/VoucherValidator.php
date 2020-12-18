<?php

namespace SilverCart\Voucher\Security;

use SilverCart\Commission\Model\Partner;
use SilverCart\Voucher\Model\Voucher;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;

/**
 * 
 * @package SilverCart
 * @subpackage Voucher\Security
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 28.09.2020
 * @copyright 2020 pixeltricks GmbH
 * @license see license file in modules root directory
 *
 * Additional required fields can also be set via config API, eg.
 * <code>
 * SilverCart\Voucher\Security\VoucherValidator:
 *     custom_required:
 *         - ProductNumber
 * </code>
 */
class VoucherValidator extends RequiredFields
{
    /**
     * Fields that are required by this validator
     * 
     * @var string[]
     */
    private static $custom_required = [
        'quantity',
        'TaxID',
    ];
    /**
     * Determine what voucher this validator is meant for
     * 
     * @var Voucher
     */
    protected $forVoucher = null;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        $required = func_get_args();
        if (isset($required[0]) && is_array($required[0])) {
            $required = $required[0];
        }
        $required = array_merge($required, $this->config()->custom_required);
        parent::__construct(array_unique($required));
    }

    /**
     * Get the Voucher this validator applies to.
     * 
     * @return Voucher
     */
    public function getForVoucher() : ?Voucher
    {
        return $this->forVoucher;
    }

    /**
     * Set the Voucher this validator applies to.
     * 
     * @param Voucher $value Voucher
     * 
     * @return $this
     */
    public function setForVoucher(Voucher $value) : VoucherValidator
    {
        $this->forVoucher = $value;
        return $this;
    }

    /**
     * Check if the submitted voucher data is valid (server-side)
     *
     * Check if a voucher with that code doesn't already exist, or if it does
     * that it is this voucher.
     *
     * @param array $data Submitted data
     * 
     * @return bool
     */
    public function php($data) : bool
    {
        $valid           = parent::php($data);
        $identifierField = 'code';
        $id              = isset($data['ID']) ? (int) $data['ID'] : 0;
        if (isset($data[$identifierField])) {
            if (!$id && ($ctrl = $this->form->getController())) {
                if ($ctrl instanceof GridFieldDetailForm_ItemRequest
                 && $record = $ctrl->getRecord()
                ) {
                    $id = $record->ID;
                }
            }
            if ((int) $id === 0
             && $voucher = $this->getForVoucher()
            ) {
                $id = $voucher->exists() ? $voucher->ID : 0;
            }
            $data['ID'] = $id;
            $vouchers   = Voucher::get()->filter($identifierField, $data[$identifierField]);
            if ($id > 0) {
                $vouchers = $vouchers->exclude('ID', $id);
            }

            if ($vouchers->count() > 0) {
                $this->validationError(
                    $identifierField,
                    _t(Voucher::class . '.ValidationVoucherExists', 'A voucher already exists with the same {identifier}', ['identifier' => Voucher::singleton()->fieldLabel($identifierField)]),
                    'required'
                );
                $valid = false;
            } elseif (class_exists(Partner::class)) {
                $partners = Partner::get()->filter(ucfirst($identifierField), $data[$identifierField]);
                if ($partners->count() > 0) {
                    $this->validationError(
                        $identifierField,
                        _t(Voucher::class . '.ValidationPartnerExists', 'A partner already exists with the same {identifier}', ['identifier' => Partner::singleton()->fieldLabel($identifierField)]),
                        'required'
                    );
                    $valid = false;
                }
            }
        }
        // Execute the validators on the extensions
        $results   = $this->extend('updatePHP', $data, $this->form);
        $results[] = $valid;
        return min($results);
    }
}