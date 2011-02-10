<?php
/**
 * Extends the article object with voucher specific fields and methods.
 *
 * @package teleapotheke
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 10.02.2011
 * @license none
 */
class SilvercartVoucherArticleRole extends DataObjectDecorator {

    /**
     * Defines relations, attributes and some settings this class.
     *
     * @return array for extended attributes and relationships
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 10.20.2011
     */
    public function extraStatics() {
        return array(
            'has_one' => array(
                'SilvercartNaturalRebateVoucher' => 'SilvercartVoucher'
            )
        );
    }
}