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
 * Extends the SilvercartOrderPosition object with voucher specific fields and methods.
 *
 * @package Silvercart
 * @package Vouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 13.04.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartVoucherOrderPosition extends DataObjectDecorator {

    /**
     * Defines additional relations and attributes.
     *
     * @return array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 13.04.2011
     */
    public function extraStatics() {
        return array(
            'db' => array(
                'SilvercartVoucherCode'  => 'Text',
                'SilvercartVoucherValue' => 'Money'
            )
        );
    }

    /**
     * Returns the voucher codes for this order position.
     *
     * @return DataObjectSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 13.04.2011
     */
    public function getVoucherCodes() {
        $voucherCodes = array();

        if (strpos($this->owner->SilvercartVoucherCode, ',') !== false) {
            $codes = explode(', ', $this->owner->SilvercartVoucherCode);

            foreach ($codes as $code) {
                $voucherCodes[] = array(
                    'code' => $code
                );
            }
        } else {
            $voucherCodes[] = array(
                'code' => $this->owner->SilvercartVoucherCode
            );
        }

        return new DataObjectSet($voucherCodes);
    }
}
