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
 * 
 * @property bool $implicatePosition Is this an implicate position?
 * 
 * @method ShoppingCart ShoppingCart() Returns the related ShoppingCart.
 * @method Voucher      Voucher()      Returns the related Voucher.
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
            $position->implicatePosition = true;
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
     * Checks if the given shoppingCartID has not Combinable Vouchers
     *
     * @param int $shoppingCartID
     *
     * @return bool
     *
     * @author Jan Lehmann <jlehmann@pixeltricks.de>
     * @since 09.10.2023
     */
    public static function notCombinableVoucherInShoppingCart(int $shoppingCartID) : bool
    {
        $notCombinableVoucherInShoppingCart = false;
        $shoppingCartPositions = self::get()->filter([
            'ShoppingCartID' => $shoppingCartID,
        ]);
        foreach ($shoppingCartPositions as $key => $shoppingCartPosition) {
            if($shoppingCartPosition->Voucher->notCombinable) {
                $notCombinableVoucherInShoppingCart = true;
            }
        }
        return $notCombinableVoucherInShoppingCart;
    }

    /**
     * Checks if a record with the given shoppingCartID exists.
     *
     * @param int $shoppingCartID
     *
     * @return bool
     *
     * @author Jan Lehmann <jlehmann@pixeltricks.de>
     * @since 09.10.2023
     */
    public static function shoppingCartHasVouchers (int $shoppingCartID) : bool
    {
        return self::get()->filter(['ShoppingCartID' => $shoppingCartID])->exists();
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