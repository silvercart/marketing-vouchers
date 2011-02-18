<?php
/**
 * This class connects a SilvercartVoucher to a ShoppingCart Instance and
 * provides methods to add, remove and check for those connections.
 *
 * @package SilvcercartVouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 03.02.2011
 * @license none
 */
class SilvercartVoucherShoppingCartPosition extends DataObject {

    /**
     * Attributes.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 03.02.2011
     */
    public static $db = array(
        'implicatePosition' => 'Boolean'
    );

    /**
     * Has-one Relationships.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 03.02.2011
     */
    public static $has_one = array(
        'SilvercartShoppingCart' => 'SilvercartShoppingCart',
        'SilvercartVoucher'      => 'SilvercartVoucher'
    );

    /**
     * Save a record to the database.
     *
     * If there's already a record with the same shoppingCartID <-> voucherID
     * combination, then nothing is done.
     *
     * @param int $silvercartShoppingCartID The ID of the shopping cart record
     * @param int $voucherID      The ID of the voucher record
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 03.02.2011
     */
    public static function add($silvercartShoppingCartID, $voucherID) {
        $silvercartVoucherShoppingCartPosition = DataObject::get_one(
            'SilvercartVoucherShoppingCartPosition',
            sprintf(
                "`SilvercartShoppingCartID` = '%d' AND `SilvercartVoucherID` = '%d'",
                $silvercartShoppingCartID,
                $voucherID
            )
        );

        if (!$silvercartVoucherShoppingCartPosition) {
            $silvercartVoucherShoppingCartPosition = new SilvercartVoucherShoppingCartPosition();
            $silvercartVoucherShoppingCartPosition->setField('SilvercartShoppingCartID',      $silvercartShoppingCartID);
            $silvercartVoucherShoppingCartPosition->setField('SilvercartVoucherID', $voucherID);
            $silvercartVoucherShoppingCartPosition->setField('implicatePosition',   1);
            $silvercartVoucherShoppingCartPosition->write();
        }
    }

    /**
     * Remove a record from the database.
     *
     * @param int $silvercartShoppingCartID The ID of the shopping cart record
     * @param int $voucherID      The ID of the voucher record
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 03.02.2011
     */
    public static function remove($silvercartShoppingCartID, $voucherID) {
        $silvercartVoucherShoppingCartPosition = DataObject::get_one(
            'SilvercartVoucherShoppingCartPosition',
            sprintf(
                "`SilvercartShoppingCartID` = '%d' AND `SilvercartVoucherID` = '%d'",
                $silvercartShoppingCartID,
                $voucherID
            )
        );

        if ($silvercartVoucherShoppingCartPosition) {
            $silvercartVoucherShoppingCartPosition->delete();
        }
    }

    /**
     * Checks if a record with the given shoppingCartID <-> voucherID
     * combination exists.
     *
     * @param int $silvercartShoppingCartID The ID of the shopping cart record
     * @param int $voucherID      The ID of the voucher record
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 03.02.2011
     */
    public static function combinationExists($silvercartShoppingCartID, $voucherID) {
        $recordExists = false;

        $silvercartVoucherShoppingCartPosition = DataObject::get_one(
            'SilvercartVoucherShoppingCartPosition',
            sprintf(
                "`SilvercartShoppingCartID` = '%d' AND `SilvercartVoucherID` = '%d'",
                $silvercartShoppingCartID,
                $voucherID
            )
        );

        if ($silvercartVoucherShoppingCartPosition) {
            $recordExists = true;
        }

        return $recordExists;
    }

    /**
     * Returns the asked for object if it exists in the database.
     *
     * @param int $silvercartShoppingCartID The ID of the shopping cart record
     * @param int $voucherID      The ID of the voucher record
     *
     * @return mixed SilvercartVoucherShoppingCartPosition|bool false
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 03.02.2011
     */
    public static function get($silvercartShoppingCartID, $voucherID) {
        $record = false;

        $silvercartVoucherShoppingCartPosition = DataObject::get_one(
            'SilvercartVoucherShoppingCartPosition',
            sprintf(
                "`SilvercartShoppingCartID` = '%d' AND `SilvercartVoucherID` = '%d'",
                $silvercartShoppingCartID,
                $voucherID
            )
        );

        if ($silvercartVoucherShoppingCartPosition) {
            $record = $silvercartVoucherShoppingCartPosition;
        }

        return $record;
    }

    /**
     * Set the implication status.
     *
     * @param bool $status The implication status
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 03.02.2011
     */
    public function setImplicationStatus($status) {
        $status = (bool) $status;
        
        $this->setField('implicatePosition', $status);
        $this->write();
    }
}
