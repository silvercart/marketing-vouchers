<?php

namespace SilverCart\Voucher\Model;

use SilverCart\Dev\Tools;
use SilverCart\Model\Order\ShoppingCart;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

/**
 * Manages the history of a voucher.
 *
 * @package SilverCart
 * @subpackage Voucher\Model
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 14.05.2020
 * @copyright 2020 pixeltricks GmbH
 * @license see license file in modules root directory
 */
class VoucherHistory extends DataObject
{
    /**
     * Table name
     *
     * @var string
     */
    private static $table_name = 'SilvercartVoucherHistory';
    /**
     * Attributes.
     *
     * @var array
     */
    private static $db = [
        'action' => "Enum('redeemed,manuallyRedeemed,removed,manuallyRemoved,activated,deactivated','redeemed')"
    ];
    /**
     * Has-one relationships.
     *
     * @var array
     */
    private static $has_one = [
        'Member'        => Member::class,
        'VoucherObject' => Voucher::class,
        'ShoppingCart'  => ShoppingCart::class
    ];
    /**
     * Set default sort field.
     *
     * @var string
     */
    private static $default_sort = 'Created DESC';

    /**
     * Returns the translated plural name of the object. If no translation exists
     * the class name will be returned.
     *
     * @return string
     */
    public function plural_name() : string
    {
        return Tools::plural_name_for($this);
    }

    /**
     * Returns the translated singular name of the object. If no translation exists
     * the class name will be returned.
     *
     * @return string
     */
    public function singular_name() : string
    {
        return Tools::singular_name_for($this);
    }
    
    /**
     * Summary fields.
     *
     * @var array
     */
    public function summaryFields() : array
    {
        return [
            'action'             => 'action',
            'Member.FirstName'   => 'FirstName',
            'Member.Surname'     => 'Surname',
            'VoucherObject.code' => 'Code',
            'Created'            => 'Created',
        ];
    }

    /**
     * Adds a history entry for a voucher.
     *
     * @param Voucher $voucher The voucher object
     * @param Member  $member  The member object
     * @param string  $action  The action to document
     *
     * @return VoucherHistory
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 14.05.2020
     */
    public function add(Voucher $voucher, Member $member, string $action) : VoucherHistory
    {
        $this->MemberID        = $member->ID;
        $this->VoucherObjectID = $voucher->ID;
        $this->ShoppingCartID  = $member->ShoppingCartID;
        $this->action          = $action;
        $this->write();
        $voucher->VoucherHistory()->add($this);
        return $this;
    }
}