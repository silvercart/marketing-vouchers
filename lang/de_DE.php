<?php
/**
 * German (Germany) language pack
 *
 * @package Silvercart
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 24.01.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @ignore
 */
i18n::include_locale_file('silvercart', 'en_US');

global $lang;

if (array_key_exists('de_DE', $lang) && is_array($lang['de_DE'])) {
    $lang['de_DE'] = array_merge($lang['en_US'], $lang['de_DE']);
} else {
    $lang['de_DE'] = $lang['en_US'];
}

$lang['de_DE']['SilvercartVoucher']['CODE']                                         = 'Gutschein-Code';
$lang['de_DE']['SilvercartVoucher']['CREATED']                                      = 'Erstellt am';
$lang['de_DE']['SilvercartVoucher']['ERRORMESSAGE-SHOPPINGCARTVALUE_NOT_VALID']     = 'Der Warenkorbwert ist nicht passend.';
$lang['de_DE']['SilvercartVoucher']['ERRORMESSAGE-SHOPPINGCARTITEMS_NOT_VALID']     = 'Dieser Gutschein kann nicht für die Waren eingelöst werden, die sich in Ihrem Warenkorb befinden.';
$lang['de_DE']['SilvercartVoucher']['ERRORMESSAGE-CODE_NOT_VALID']                  = 'Dieser Gutscheincode ist nicht gültig.';
$lang['de_DE']['SilvercartVoucher']['ERRORMESSAGE-CUSTOMER_MUST_BE_REGISTERED']     = 'Sie müssen sich registrieren, um diesen Gutschein einzulösen, da dieser Gutschein bei Einlösung an den Kunde gebunden wird.<br />Dadurch können Sie diesen Gutschein für mehrere Einkäufe verwenden, falls Ihr Einkaufswert niedriger ist als der Gutscheinwert.<br /><a href="%s">Klicken Sie hier, um zum Registrierungsformular zu gelangen.</a>';
$lang['de_DE']['SilvercartVoucher']['ERRORMESSAGE-CUSTOMER_NOT_ELIGIBLE']           = 'Sie dürfen diesen Gutschein nicht einlösen.';
$lang['de_DE']['SilvercartVoucher']['ERRORMESSAGE-NOT_REDEEMABLE']                  = 'Der Gutschein kann nicht eingelöst werden.';
$lang['de_DE']['SilvercartVoucher']['ERRORMESSAGE-ALREADY_IN_SHOPPINGCART']         = 'Dieser Gutschein befindet sich schon in Ihrem Warenkorb.';
$lang['de_DE']['SilvercartVoucher']['ERRORMESSAGE-VOUCHER_ALREADY_OWNED']           = 'Dieser Gutschein wurde schon von einer anderen Person eingelöst.';
$lang['de_DE']['SilvercartVoucher']['ISACTIVE']                                     = 'Ist Aktiv';
$lang['de_DE']['SilvercartVoucher']['LABEL-SHOPPINGCART_REMOVE']                    = 'Entfernen';
$lang['de_DE']['SilvercartVoucher']['LABEL-REDEEM']                                 = 'Einlösen';
$lang['de_DE']['SilvercartVoucher']['QUANTITY']                                     = 'Anzahl';
$lang['de_DE']['SilvercartVoucher']['VALUE']                                        = 'Wert';

$lang['de_DE']['SilvercartVoucher']['SILVERCARTABSOLUTEREBATEGIFTVOUCHER.SINGULARNAME']             = 'Geschenkgutschein';
$lang['de_DE']['SilvercartVoucher']['SILVERCARTABSOLUTEREBATEGIFTVOUCHER.PLURALNAME']               = 'Geschenkgutscheine';
$lang['de_DE']['SilvercartVoucher']['SILVERCARTABSOLUTEREBATEGIFTVOUCHER_BLUEPRINT.SINGULARNAME']   = 'Geschenkgutschein Vorlage';
$lang['de_DE']['SilvercartVoucher']['SILVERCARTABSOLUTEREBATEGIFTVOUCHER_BLUEPRINT.PLURALNAME']     = 'Geschenkgutscheine Vorlage';
$lang['de_DE']['SilvercartVoucher']['SILVERCARTABSOLUTEREBATEVOUCHER.SINGULARNAME']                 = 'Wertgutschein';
$lang['de_DE']['SilvercartVoucher']['SILVERCARTABSOLUTEREBATEVOUCHER.PLURALNAME']                   = 'Wertgutscheine';
$lang['de_DE']['SilvercartVoucher']['SILVERCARTRELATIVEREBATEVOUCHER.SINGULARNAME']                 = 'Rabattgutschein';
$lang['de_DE']['SilvercartVoucher']['SILVERCARTRELATIVEREBATEVOUCHER.PLURALNAME']                   = 'Rabattgutscheine';
$lang['de_DE']['SilvercartVoucher']['SILVERCARTNATURALREBATEVOUCHER.SINGULARNAME']                  = 'Warengutschein';
$lang['de_DE']['SilvercartVoucher']['SILVERCARTNATURALREBATEVOUCHER.PLURALNAME']                    = 'Warengutscheine';

$lang['de_DE']['SilvercartVoucherOrderDetailPage']['SINGULARVOUCHERTITLE']          = ' Der Gutschein-Code lautet';
$lang['de_DE']['SilvercartVoucherOrderDetailPage']['PLURALVOUCHERTITLE']            = 'Die Gutschein-Codes lauten:';
$lang['de_DE']['SilvercartVoucherOrderDetailPage']['SINGULARVOUCHERVALUETITLE']     = 'Der Wert des Gutscheins beträgt';
$lang['de_DE']['SilvercartVoucherOrderDetailPage']['PLURALVOUCHERVALUETITLE']       = 'Der Wert jedes Gutscheins beträgt';
$lang['de_DE']['SilvercartVoucherOrderDetailPage']['WARNING_PAYBEFOREREDEEMING_SINGULAR']   = 'Beachten Sie bitte, dass die Bestellung erst bezahlt werden muss, bevor der Gutscheine eingelöst werden kann.';
$lang['de_DE']['SilvercartVoucherOrderDetailPage']['WARNING_PAYBEFOREREDEEMING_PLURAL']     = 'Beachten Sie bitte, dass die Bestellung erst bezahlt werden muss, bevor die Gutscheine eingelöst werden können.';
