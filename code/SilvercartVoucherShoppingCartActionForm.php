<?php
/**
 * This form provides input fields for the voucher code.
 *
 * The user can redeem a voucher with this form.
 *
 * @copyright pixeltricks GmbH
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @license none
 * @since 21.01.2011
 */
class SilvercartVoucherShoppingCartActionForm extends CustomHtmlForm {
    /**
     * Enthaelt die zu pruefenden und zu verarbeitenden Formularfelder.
     *
     * @var array
     */
    protected $formFields = array(
        'VoucherCode' => array(
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
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 21.01.2011
     */
    protected function fillInFieldValues() {
        parent::fillInFieldValues();

        $sessionData = Session::get($this->sessionStatusMessageId);

        if ($sessionData) {
            if (isset($sessionData['Message'])) {
                $this->addMessage($sessionData['Message']);
            }

            if (isset($sessionData['ErrorMessages'])) {
                $this->errorMessages = $sessionData['ErrorMessages'];
            }
            Session::clear($this->sessionStatusMessageId);
        }
    }

    /**
     * Wird ausgefuehrt, wenn nach dem Senden des Formulars keine Validierungs-
     * fehler aufgetreten sind.
     *
     * @param SS_HTTPRequest $data     session data
     * @param Form           $form     form object
     * @param array          $formData CustomHTMLForms session data
     *
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 21.01.2011
     * @return void
     */
    protected function submitSuccess($data, $form, $formData) {
        $status      = array('error' => false, 'messages' => array());
        $voucherCode = Convert::raw2sql($formData['VoucherCode']);
        $voucher     = DataObject::get_one(
            'SilvercartVoucher',
            sprintf(
                "code LIKE '%s'",
                $voucherCode
            )
        );
        $member         = Member::currentUser();
        $shoppingCart   = $member->shoppingCart();

        if ($voucher) {
            $status = $voucher->checkifAllowedInShoppingCart($voucherCode, $member, $shoppingCart);
        } else {
            $status['error']        = true;
            $status['messages'][]   = 'Dieser Gutscheincode ist nicht gültig.';
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
        Director::redirect($this->controller->Link());
    }

    /**
     * Setzt eine Statusmeldung in der Session, die nach einem Reload der
     * Seite im Formular angezeigt wird.
     *
     * @param string $text          Der Text der  Meldung, der angezeigt werden soll.
     * @param bool   $bidSuccessful Gibt an, ob das Gebot erfolgreich war.
     * @param bool   $bidGiven      Gibt an, ob das Gebot abgegeben wurde.
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