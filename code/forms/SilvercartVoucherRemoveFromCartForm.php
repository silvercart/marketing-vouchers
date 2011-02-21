<?php
/**
 * This form provides fields to remove a voucher from the shopping cart.
 *
 * The user can remove a voucher from the shopping cart with this form.
 *
 * @copyright pixeltricks GmbH
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @license none
 * @since 25.01.2011
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
        'SilvercartVoucherID' => array(
            'type'          => 'HiddenField',
            'value'         => ''
        )
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

        Director::redirect($this->controller->Link());
    }
}
