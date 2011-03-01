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
 * Manages the history of a voucher.
 *
 * @package Silvercart
 * @subpackage Vouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 20.01.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartVoucherHistory extends DataObject {

    /**
     * Attributes.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 24.01.2011
     */
    public static $db = array(
        'action' => "Enum('redeemed,manuallyRedeemed,removed,manuallyRemoved,activated,deactivated','redeemed')"
    );

    /**
     * Has-one relationships.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 24.01.2011
     */
    public static $has_one = array(
        'Member'                  => 'Member',
        'SilvercartVoucherObject' => 'SilvercartVoucher',
        'SilvercartShoppingCart'  => 'SilvercartShoppingCart'
    );

    /**
     * Summary fields for DataObjectManager.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 24.01.2011
     */
    public static $summary_fields = array(
        'Member.FirstName',
        'Member.Surname',
        'SilvercartVoucherObject.code',
        'Created'
    );

    /**
     * Adds a history entry for a voucher.
     *
     * @param SilvercartVoucher $voucher The voucher object
     * @param Member            $member  The member object
     * @param string            $action  The action to document
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 24.01.2011
     */
    public function add(SilvercartVoucher $voucher, Member $member, $action) {
        $this->MemberID= $member->ID;
        $this->SilvercartVoucherObjectID = $voucher->ID;
        $this->SilvercartShoppingCartID  = $member->SilvercartShoppingCart()->ID;
        $this->action                    = $action;
        $this->write();

        $voucher->SilvercartVoucherHistory()->add($this);
    }
}
