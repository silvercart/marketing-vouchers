<?php

namespace SilverCart\Voucher\Model;

use SilverCart\Model\Order\ShoppingCart;
use SilverStripe\ORM\DataObject;

/**
 * This class connects a Voucher to a ShoppingCart Instance and
 * provides methods to add, remove and check for those connections.
 *
 * @package SilverCart
 * @subpackage Voucher\Model
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 14.05.2020
 * @copyright 2020 pixeltricks GmbH
 * @license see license file in modules root directory
 */
class ShoppingCartPosition extends DataObject
{
    /**
     * Table name
     *
     * @var string
     */
    private static $table_name = 'SilvercartVoucherShoppingCartPosition';
    /**
     * Attributes.
     *
     * @var array
     */
    private static $db = [
        'implicatePosition' => 'Boolean',
    ];
    /**
     * Has-one Relationships.
     *
     * @var array
     */
    private static $has_one = [
        'ShoppingCart' => ShoppingCart::class,
        'Voucher'      => Voucher::class,
    ];

    /**
     * Save a record to the database.
     *
     * If there's already a record with the same shoppingCartID <-> voucherID
     * combination, then nothing is done.
     *
     * @param int $shoppingCartID The ID of the shopping cart record
     * @param int $voucherID      The ID of the voucher record
     *
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 14.05.2020
     */
    public static function add(int $shoppingCartID, int $voucherID) : void
    {
        if (!self::combinationExists($shoppingCartID, $voucherID)) {
            $position = self::create();
            $position->ShoppingCartID    = $shoppingCartID;
            $position->VoucherID         = $voucherID;
            $position->implicatePosition = 1;
            $position->write();
        }
    }

    /**
     * Remove a record from the database.
     *
     * @param int $shoppingCartID The ID of the shopping cart record
     * @param int $voucherID      The ID of the voucher record
     *
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 14.05.2020
     */
    public static function remove(int $shoppingCartID, int $voucherID) : void
    {
        $position = self::get()->filter([
            'ShoppingCartID' => $shoppingCartID,
            'VoucherID'      => $voucherID,
        ])->first();
        if ($position instanceof ShoppingCartPosition) {
            $position->delete();
        }
    }

    /**
     * Checks if a record with the given shoppingCartID <-> voucherID
     * combination exists.
     *
     * @param int $shoppingCartID The ID of the shopping cart record
     * @param int $voucherID      The ID of the voucher record
     *
     * @return bool
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 14.05.2020
     */
    public static function combinationExists(int $shoppingCartID, int $voucherID) : bool
    {
        return self::get()->filter([
            'ShoppingCartID' => $shoppingCartID,
            'VoucherID'      => $voucherID,
        ])->exists();
    }

    /**
     * Returns the asked for object if it exists in the database.
     *
     * @param int $shoppingCartID The ID of the shopping cart record
     * @param int $voucherID      The ID of the voucher record
     *
     * @return ShoppingCartPosition|NULL
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 14.05.2020
     */
    public static function getVoucherShoppingCartPosition(int $shoppingCartID, int $voucherID) : ?ShoppingCartPosition
    {
        return self::get()->filter([
            'ShoppingCartID' => $shoppingCartID,
            'VoucherID'      => $voucherID,
        ])->first();
    }

    /**
     * Set the implication status.
     *
     * @param bool $status The implication status
     *
     * @return ShoppingCartPosition
     */
    public function setImplicationStatus(bool $status) : ShoppingCartPosition
    {
        $this->implicatePosition = $status;
        $this->write();
        return $this;
    }
}