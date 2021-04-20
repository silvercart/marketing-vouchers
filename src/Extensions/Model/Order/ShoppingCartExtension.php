<?php

namespace SilverCart\Voucher\Extensions\Model\Order;

use SilverCart\Voucher\Model\ShoppingCartPosition as VoucherShoppingCartPosition;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\ORM\DataExtension;

/**
 * Extension for SilverCart ShoppingCart.
 * 
 * @package SilverCart
 * @subpackage Voucher\Extensions\Model\Order
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 29.09.2020
 * @copyright 2020 pixeltricks GmbH
 * @license see license file in modules root directory
 * 
 * @property \SilverCart\Model\Order\ShoppingCart $owner Owner
 */
class ShoppingCartExtension extends DataExtension
{
    /**
     * Has many relations.
     *
     * @var array
     */
    private static $has_many = [
        'VoucherPositions' => VoucherShoppingCartPosition::class . '.ShoppingCart',
    ];
    
    /**
     * Updates the field labels.
     * 
     * @param array &$labels Labels to update
     * 
     * @return void
     */
    public function updateFieldLabels(&$labels) : void
    {
        $labels['VoucherPositions'] = _t(VoucherShoppingCartPosition::class . '.PLURALNAME', 'Voucher Positions');
    }
    
    /**
     * Updates the CMS fields.
     * 
     * @param FieldList $fields Fields
     * 
     * @return void
     */
    public function updateCMSFields(FieldList $fields) : void
    {
        $positionsField = $fields->dataFieldByName('VoucherPositions');
        if ($positionsField instanceof GridField) {
            $positionFieldConfig = $positionsField->getConfig();
            $positionFieldConfig->removeComponentsByType(GridFieldAddExistingAutocompleter::class);
            $positionFieldConfig->removeComponentsByType(GridFieldAddNewButton::class);
            $deleteAction        = $positionFieldConfig->getComponentByType(GridFieldDeleteAction::class);
            /* @var $deleteAction GridFieldDeleteAction */
            $deleteAction->setRemoveRelation(false);
        }
    }
}