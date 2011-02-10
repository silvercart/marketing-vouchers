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

    /**
     * The URL segment
     *
     * @var string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 10.02.2011
     */
    public static $url_segment = 'vouchers';

    /**
     * The menu title
     *
     * @var string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 10.02.2011
     */
    public static $menu_title  = 'Gutscheine';

    /**
     * Managed models
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 10.02.2011
     */
    public static $managed_models = array(
        'SilvercartAbsoluteRebateVoucher',
        'SilvercartNaturalRebateVoucher',
        'SilvercartRelativeRebateVoucher',
        'SilvercartAbsoluteRebateGiftVoucherBlueprint'
    );
}