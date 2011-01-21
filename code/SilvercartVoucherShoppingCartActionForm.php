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
    protected $formFields = array
    (
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
        $error       = false;
        $messages    = array();
        $voucherCode = $formData['VoucherCode'];
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

            if (!$error && !$voucher->isCustomerEligible($member)) {
                $error      = true;
                $messages[] = 'Sie dürfen diesen Gutschein nicht einlösen.';
            }

            if (!$error && !$voucher->isShoppingCartAmountValid($shoppingCart->getPrice())) {
                $error      = true;
                $messages[] = 'Der Warenkorbwert ist nicht passend.';
            }

            if (!$error && !$voucher->isRedeemable()) {
                $error      = true;
                $messages[] = 'Der Gutschein kann nicht eingelöst werden.';
            }

            if (!$error && !$voucher->isValidForShoppingCartItems($shoppingCart->positions())) {
                $error      = true;
                $messages[] = 'Dieser Gutschein kann nicht für die Waren eingelöst werden, die sich in Ihrem Warenkorb befinden.';
            }

        } else {
            $error = true;
        }

        if ($error) {
            print "Gutschein kann nicht eingelöst werden.<br />";
            print_r($messages);
        } else {
            print "Gutschein kann eingelöst werden.";
        }
        //Director::redirect($this->controller->Link());
    }
}