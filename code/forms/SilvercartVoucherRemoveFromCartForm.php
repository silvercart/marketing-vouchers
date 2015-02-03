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
 * This form provides fields to remove a voucher from the shopping cart.
 *
 * The user can remove a voucher from the shopping cart with this form.
 *
 * @package Silvercart
 * @subpackage Vouchers
 * @copyright pixeltricks GmbH
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @since 25.01.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartVoucherRemoveFromCartForm extends CustomHtmlForm {
    
    /**
     * Enthaelt die zu pruefenden und zu verarbeitenden Formularfelder.
     *
     * @var array
     * 
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 25.01.2010
     */
    protected $formFields = array(
    );

    /**
     * form settings, mainly submit button´s name
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 25.01.2010
     * @return void
     */
    protected $preferences = array(
        'submitButtonTitle'         => 'entfernen',
        'doJsValidationScrolling'   => false
    );

    /**
     * Alternative method to define preferences.
     *
     * @return array
     * 
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 01.12.2011
     */
    public function preferences() {
        $preferences = SilvercartPlugin::call($this, 'updatePreferences', array($this->preferences), true, array());
        
        if (is_array($preferences) &&
            count($preferences) > 0) {
            
            $this->preferences = $preferences[0];
        }
        
        return $this->preferences;
    }
    
    /**
     * Setzt Initialwerte in Formularfeldern.
     *
     * @return void
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 25.01.2011
     */
    protected function fillInFieldValues() {
        $this->preferences['submitButtonTitle'] = _t('SilvercartVoucher.LABEL-SHOPPINGCART_REMOVE', 'entfernen');
        
        parent::fillInFieldValues();
    }

    /**
     * Wird ausgefuehrt, wenn nach dem Senden des Formulars keine Validierungs-
     * fehler aufgetreten sind.
     *
     * @param SS_HTTPRequest $data     session data
     * @param Form           $form     form object
     * @param array          $formData CustomHTMLForms session data
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 25.01.2011
     * @return void
     */
    protected function submitSuccess($data, $form, $formData) {
        $member  = Member::currentUser();
        $voucher = DataObject::get_by_id(
            'SilvercartVoucher',
            $formData['SilvercartVoucherID']
        );

        if ($voucher) {
            $voucher->removeFromShoppingCart($member, 'manuallyRemoved');
        }

        $this->Controller()->redirect($this->Controller()->Link());
    }
}
