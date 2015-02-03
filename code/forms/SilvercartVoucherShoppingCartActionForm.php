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
 * This form provides input fields for the voucher code.
 *
 * The user can redeem a voucher with this form.
 *
 * @package Silvercart
 * @subpackage Vouchers
 * @copyright pixeltricks GmbH
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @since 21.01.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartVoucherShoppingCartActionForm extends CustomHtmlForm {
    /**
     * Enthaelt die zu pruefenden und zu verarbeitenden Formularfelder.
     *
     * @var array
     */
    protected $formFields = array(
        'SilvercartVoucherCode' => array(
            'type'              => 'TextField',
            'title'             => 'Gutschein Code',
            'value'             => '',
            'checkRequirements' => array(
                'isFilledIn'    => true
            )
        )
    );

    /**
     * The session id used for saving status messages specific to this form.
     *
     * @var string
     * 
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 24.01.2011
     */
    protected $sessionStatusMessageId = 'SilvercartShoppingCartVoucher';

    /**
     * form settings, mainly submit button´s name
     *
     * @var array
     *
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 1.11.2010
     * @return void
     */
    protected $preferences = array(
        'submitButtonTitle'         => 'Einlösen',
        'doJsValidationScrolling'   => false
    );

    /**
     * Setzt Initialwerte in Formularfeldern.
     *
     * @return void
     * @author Roland Lehmann <rlehmann@pixeltricks.de>, Patrick Schneider <pschneider@pixeltricks.de>
     * @since 06.12.2012
     */
    protected function fillInFieldValues() {
        parent::fillInFieldValues();

        $sessionData = Session::get($this->sessionStatusMessageId);

        if ($sessionData) {
            if (isset($sessionData['Message'])) {
                $this->addErrorMessage('SilvercartVoucherCode', $sessionData['Message']);
            }

            if (isset($sessionData['ErrorMessages'])) {
                $this->errorMessages = $sessionData['ErrorMessages'];
            }
            Session::clear($this->sessionStatusMessageId);
        }

        $this->preferences['submitButtonTitle'] = _t('SilvercartVoucher.LABEL-REDEEM');
    }

    /**
     * Wird ausgefuehrt, wenn nach dem Senden des Formulars keine Validierungs-
     * fehler aufgetreten sind.
     *
     * @param SS_HTTPRequest $data     session data
     * @param Form           $form     form object
     * @param array          $formData CustomHTMLForms session data
     *
     * @author Roland Lehmann <rlehmann@pixeltricks.de>, Patrick Schneider <pschneider@pixeltricks.de>
     * @since 06.12.2012
     * @return void
     */
    protected function submitSuccess($data, $form, $formData) {
        $status      = array(
            'error' => false,
            'messages' => array()
        );
        $voucherCode = Convert::raw2sql($formData['SilvercartVoucherCode']);
        $voucher     = DataObject::get_one(
            'SilvercartVoucher',
            sprintf(
                "code LIKE '%s'",
                $voucherCode
            )
        );
        $member         = Member::currentUser();
        $shoppingCart   = $member->SilvercartShoppingCart();

        if ($voucher) {
            $status = $voucher->checkifAllowedInShoppingCart($voucher, $member, $shoppingCart);
        } else {
            $status['error']        = true;
            $status['messages'][]   = _t('SilvercartVoucher.ERRORMESSAGE-CODE_NOT_VALID');
        }

        if ($status['error']) {
            $errorMessage = '';

            foreach ($status['messages'] as $message) {
                $errorMessage .= '<p>'.$message.'</p>';
            }

            $this->setSessionStatus($errorMessage);
        } else {
            $voucher->redeem($member, 'manuallyRedeemed');
        }
        $this->Controller()->redirect($this->Controller()->Link());
    }

    /**
     * Setzt eine Statusmeldung in der Session, die nach einem Reload der
     * Seite im Formular angezeigt wird.
     *
     * @param string $text Der Text der  Meldung, der angezeigt werden soll.
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 02.12.2010
     */
    protected function setSessionStatus($text) {
        Session::set($this->sessionStatusMessageId,
            array(
                'Message' => $text
            )
        );
    }
}
