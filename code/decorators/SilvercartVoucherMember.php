<?php
/**
 * Copyright 2011 pixeltricks GmbH
 *
 * This file is part of SilverCart.
 *
 * SilverCart is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SilverCart is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with SilverCart.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Extends the member object with voucher specific fields and methods.
 *
 * @package Silvercart
 * @package Vouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 24.01.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartVoucherMember extends DataObjectDecorator {
    
    /**
     * defines relations, attributes and some settings this class.
     *
     * @return array for denfining and configuring the class via the framework
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 18.10.2010
     */
    public function extraStatics() {
        return array(
            'has_many' => array(
                'SilvercartAbsoluteRebateGiftVouchers' => 'SilvercartAbsoluteRebateGiftVoucher'
            ),
            'many_many' => array(
                'SilvercartVouchers' => 'SilvercartVoucher'
            )
        );
    }
    
    /**
     * Manipulating CMS fields
     *
     * @param FieldSet $fields Fields to update
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 20.06.2012 
     */
    public function updateCMSFields(FieldSet $fields) {
        $fields->removeByName('SilvercartAbsoluteRebateGiftVouchers');
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
     * @since 20.06.2012
     */
    public function updateFieldLabels(&$labels) {
        $labels = array_merge(
                $labels,
                array(
                    'SilvercartAbsoluteRebateGiftVouchers'  => _t('SilvercartAbsoluteRebateGiftVoucher.PLURALNAME'),
                    'SilvercartVouchers'                    => _t('SilvercartVoucher.PLURALNAME'),
                )
        );
    }
    
}
