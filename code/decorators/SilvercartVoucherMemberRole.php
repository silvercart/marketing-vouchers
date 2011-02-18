<?php
/**
 * Extends the member object with voucher specific fields and methods.
 *
 * @package teleapotheke
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 24.01.2011
 * @license none
 */
class SilvercartVoucherMemberRole extends DataObjectDecorator {
    
    /**
     * defines relations, attributes and some settings this class.
     *
     * @return array for denfining and configuring the class via the framework
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
}
