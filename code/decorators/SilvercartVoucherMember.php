<?php
/**
 * Copyright 2015 pixeltricks GmbH
 *
 * This file is part of SilverCart.
 * 
 * @package Silvercart
 * @package Vouchers
 */

/**
 * Extends the member object with voucher specific fields and methods.
 *
 * @package Silvercart
 * @package Vouchers
 * @author Sebastian Diel <sdiel@pixeltricks.de>,
 *         Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2015 pixeltricks GmbH
 * @since 02.02.2015
 * @license see license file in modules root directory
 */
class SilvercartVoucherMember extends DataExtension {
    
    /**
     * Many many relations.
     *
     * @var array
     */
    private static $many_many = array(
        'SilvercartVouchers' => 'SilvercartVoucher',
    );
    
    /**
     * Many many extra fields.
     *
     * @var array
     */
    private static $many_many_extraFields = array(
        'SilvercartVouchers' => array(
            'remainingAmount' => 'Float',     // Amount remaining on an actual voucher
        ),
    );
    
    /**
     * Manipulating CMS fields
     *
     * @param FieldList $fields Fields to update
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 23.07.2012
     */
    public function updateCMSFields(FieldList $fields) {
        $fields->removeByName('SilvercartVouchers');
    }
    
    /**
     * Extended field labels
     *
     * @param array &$labels Field labels
     * 
     * @return void
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 23.07.2012
     */
    public function updateFieldLabels(&$labels) {
        $labels = array_merge(
                $labels,
                array(
                    'SilvercartVouchers'    => _t('SilvercartVoucher.PLURALNAME'),
                )
        );
    }
    
}
