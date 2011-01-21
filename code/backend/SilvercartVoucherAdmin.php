<?php
/**
 * Voucher administration panel.
 *
 * @package silvercartVouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 21.01.2011
 * @license none
 */
class SilvercartVoucherAdmin extends ModelAdmin {

    public static $url_segment = 'vouchers';
    public static $menu_title  = 'Gutscheine';

    public static $managed_models = array(
        'SilvercartAbsoluteRebateVoucher',
        'SilvercartNaturalRebateVoucher',
        'SilvercartRelativeRebateVoucher'
    );

    
}