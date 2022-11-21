<?php

namespace SilverCart\Voucher\Model;

use SilverCart\Dev\Tools;
use SilverCart\Model\Translation\TranslationExtension;
use SilverCart\ORM\ExtensibleDataObject;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

/**
 * Represents a Voucher translation.
 *
 *
 * @property string $VoucherTitle Title
 * @property string $Description  Description
 *
 * @method Voucher Voucher() Returns the related Voucher.
 *
 * @mixin TranslationExtension
 */

class VoucherTranslation extends DataObject
{
    use ExtensibleDataObject;

    /**
     * DB table name.
     *
     * @var string
     */
    private static $table_name = 'SilvercartVoucherTranslation';
    /**
     * DB attributes.
     *
     * @var string[]
     */
    private static $db = [
        'VoucherTitle' => 'Varchar',
        'Description'  => 'HTMLText',
    ];
    /**
     * Casted fields
     *
     * @var array
     */
    private static $casting = [
        'Title' => 'Varchar',
    ];
    /**
     * Casted fields
     *
     * @var array
     */
    private static $summary_fields = [
        'NativeNameForLocale',
        'Title',
        'Description.LimitWordCount',
    ];

    /**
     * Extensions.
     *
     * @var string[]
     */
    private static $extensions = [
        TranslationExtension::class,
    ];
    /**
     * Has one relations.
     *
     * @var string[]
     */
    private static $has_one = [
        'Voucher' => Voucher::class,
    ];

    /**
     * Returns the translated singular name.
     *
     * @return string
     */
    public function singular_name() : string
    {
        return Tools::singular_name_for($this);
    }

    /**
     * Returns the translated plural name.
     *
     * @return string
     */
    public function plural_name() : string
    {
        return Tools::plural_name_for($this);
    }

    /**
     * Field labels for display in tables.
     *
     * @param boolean $includerelations A boolean value to indicate if the labels returned include relation fields
     *
     * @return array
     */
    public function fieldLabels($includerelations = true) : array
    {
        return $this->defaultFieldLabels($includerelations, [
            'Title'                      => Voucher::singleton()->fieldLabel('VoucherTitle'),
            'VoucherTitle'               => Voucher::singleton()->fieldLabel('VoucherTitle'),
            'Description'                => Voucher::singleton()->fieldLabel('Description'),
            'Description.LimitWordCount' => Voucher::singleton()->fieldLabel('Description'),
        ]);
    }
    
    /**
     * Requires the defaults. Will add missing translations.
     * 
     * @return void
     */
    public function requireDefaultRecords() : void
    {
        $this->requireDefaultTranslations();
    }
    
    /**
     * Returns the title.
     * 
     * @return string
     */
    public function getTitle() : string
    {
        $title = $this->VoucherTitle;
        if (empty($title)) {
            return "#{$this->ID}";
        }
        return "{$title} (#{$this->ID})";
    }
}
