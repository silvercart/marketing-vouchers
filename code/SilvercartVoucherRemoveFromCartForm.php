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
        'code' => array(
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
        $customer = Member::currentUser();
        
        $voucherHistoryEntry = DataObject::get_one(
            'SilvercartVoucherHistory',
            sprintf(
                "ShoppingCartID = '%d' AND
                 CustomerID = '%d'",
                $customer->shoppingCart()->ID,
                $customer->ID
            ),
            false,
            'Created DESC'
        );

        if ($voucherHistoryEntry &&
            $voucherHistoryEntry->action != 'removed' &&
            $voucherHistoryEntry->action != 'manuallyRemoved' ) {

            $voucher = DataObject::get_by_id(
                'SilvercartVoucher',
                $voucherHistoryEntry->VoucherID
            );

            if ($voucher) {
                $voucher->removeFromShoppingCart($customer, 'manuallyRemoved');
            }
        }
        Director::redirect($this->controller->Link());
    }
}