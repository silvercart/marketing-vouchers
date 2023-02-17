<?php

namespace SilverCart\Voucher\Extensions\Model\Security;

use SilverCart\Voucher\Model\Voucher;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\ORM\DataExtension;

/**
 * Extends the member object with voucher specific fields and methods.
 *
 * @package SilverCart
 * @subpackage Voucher\Extensions\Model\Security
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @since 14.05.2020
 * @copyright 2020 pixeltricks GmbH
 * @license see license file in modules root directory
 */
class MemberExtension extends DataExtension
{
    /**
     * Many many relations.
     *
     * @var array
     */
    private static $many_many = [
        'Vouchers' => Voucher::class,
    ];
    /**
     * Many many extra fields.
     *
     * @var array
     */
    private static $many_many_extraFields = [
        'Vouchers' => [
            'remainingAmount' => 'Float', // Amount remaining on an actual voucher
        ],
    ];
    
    /**
     * Manipulating CMS fields
     *
     * @param FieldList $fields Fields to update
     * 
     * @return void
     */
    public function updateCMSFields(FieldList $fields) : void
    {
        if ($this->owner->Vouchers()->count() === 0) {
            $fields->removeByName('Vouchers');
            return;
        }
        $voucherField = $fields->dataFieldByName('Vouchers');
        if ($voucherField === null) {
            return;
        }
        /* @var $voucherField GridField */
        $config = $voucherField->getConfig();
        $config->removeComponentsByType([
            GridFieldAddExistingAutocompleter::class,
            GridFieldAddNewButton::class,
        ]);
        $columns = $config->getComponentByType(GridFieldDataColumns::class);
        /* @var $columns GridFieldDataColumns */
        $columns->setDisplayFields(array_merge(Voucher::singleton()->summaryFields(), [
            'RemainingAmountNice' => Voucher::singleton()->fieldLabel('RemainingCredit'),
        ]));
    }
    
    /**
     * Extended field labels
     *
     * @param array &$labels Field labels
     * 
     * @return void
     */
    public function updateFieldLabels(&$labels) : void
    {
        $labels = array_merge($labels, [
            'Vouchers' => Voucher::singleton()->i18n_plural_name(),
        ]);
    }
}
