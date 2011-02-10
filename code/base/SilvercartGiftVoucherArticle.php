<?php
/**
 * Represents an extended article that generates a
 * SilvercartAbsoluteRebateGiftVoucher object on conversion from shoppingcart
 * to order.
 *
 * @package SilvercartVouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 10.02.2011
 * @license none
 */
class SilvercartGiftVoucherArticle extends Article {

    /**
     * Singular name
     *
     * @var string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 10.02.2011
     */
    public static $singular_name = 'Geschenkgutschein Artikel';

    /**
     * Plural name
     *
     * @var string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 10.02.2011
     */
    public static $plural_name   = 'Geschenkgutschein Artikel';
}