<?php
/**
 * Represents an extended article that generates a
 * SilvercartAbsoluteRebateGiftVoucher object on conversion from shoppingcart
 * to order.
 * The data for the voucher originates from the related
 * SilvercartAbsoluteRebateGiftVoucherBlueprint object.
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

    /**
     * Has-one relationships
     *
     * @var string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 10.02.2011
     */
    public static $has_one = array(
        'SilvercartAbsoluteRebateGiftVoucherBlueprint' => 'SilvercartAbsoluteRebateGiftVoucherBlueprint'
    );

    /**
     * Summaryfields for display in tables.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 10.02.2011
     */
    public static $summary_fields = array(
        'Title'                                                 => 'Artikel',
        'SilvercartAbsoluteRebateGiftVoucherBlueprint.value'    => 'Gutscheinwert'
    );

    /**
     * Adjust backend fields.
     *
     * @param array $params Additional parameters
     *
     * @return FieldSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 11.02.2011
     */
    public function getCMSFields($params = null) {
        $fields = parent::getCMSFields($params);

        $fields->removeFieldFromTab('Root', 'shoppingCartPositions');
        $fields->removeFieldFromTab('Root', 'ArticleAbdaDb');
        $fields->removeByName('ArticleAbdaLaieninfo');
        $fields->removeByName('manufacturer');
        $fields->removeByName('EANCode');
        $fields->removeByName('ArticleNumberManufacturer');
        $fields->removeByName('UVP');
        $fields->removeByName('PurchasePrice');
        $fields->removeByName('SilvercartAbsoluteRebateGiftVoucherBlueprint');

        $blueprintVouchers = DataObject::get(
            'SilvercartAbsoluteRebateGiftVoucherBlueprint'
        );
        
        $fields->addFieldToTab(
            'Root.Main',
            new DropdownField(
                'SilvercartAbsoluteRebateGiftVoucherBlueprint',
                'Gutschein Vorlage',
                $blueprintVouchers->map('ID', 'value')
            )
        );

        return $fields;
    }

    /**
     * Shoppingcart hook: gets called when the shopping cart positions get
     * converted to order positions.
     *
     * Here we generate a SilvercartAbsoluteRebateGiftVoucher object and
     * relate it to our SilvercartAbsoluteRebateGiftVoucherBlueprint object.
     *
     * @param Order         $order         The Order object.
     * @param OrderPosition $orderPosition The OrderPosition object.
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 11.02.2011
     */
    public function ShoppingCartConvert(Order $order, OrderPosition $orderPosition) {
        $blueprint = $this->SilvercartAbsoluteRebateGiftVoucherBlueprint();

        if ($blueprint) {
            // Create gift voucher
            $giftVoucher = new SilvercartAbsoluteRebateGiftVoucher();
            //$giftVoucher->generateCode();
            $giftVoucher->setField('code', 'testtest');
            $giftVoucher->setField('valueAmount',                                       $blueprint->value->getAmount());
            $giftVoucher->setField('valueCurrency',                                     $blueprint->value->getCurrency());
            $giftVoucher->setField('SilvercartAbsoluteRebateGiftVoucherBlueprintID',    $blueprint->ID);
            $giftVoucher->setField('minimumShoppingCartValueAmount',                    $blueprint->minimumShoppingCartValue->getAmount());
            $giftVoucher->setField('minimumShoppingCartValueCurrency',                  $blueprint->minimumShoppingCartValue->getCurrency());
            $giftVoucher->setField('maximumShoppingCartValueAmount',                    $blueprint->maximumShoppingCartValue->getAmount());
            $giftVoucher->setField('maximumShoppingCartValueCurrency',                  $blueprint->maximumShoppingCartValue->getCurrency());
            $giftVoucher->setField('TaxID',                                             $blueprint->TaxID);
            $giftVoucher->write();

            // adjust restrictions
            foreach ($blueprint->RestrictToMember() as $member) {
                $giftVoucher->RestrictToMember()->push($member);
            }
            foreach ($blueprint->RestrictToGroup() as $group) {
                $giftVoucher->RestrictToGroup()->push($group);
            }
            foreach ($blueprint->RestrictToArticleGroupPage() as $articleGroupPage) {
                $giftVoucher->RestrictToArticleGroupPage()->push($articleGroupPage);
            }
            foreach ($blueprint->RestrictToArticle() as $article) {
                $giftVoucher->RestrictToArticle()->push($article);
            }
            $giftVoucher->write();
        }

        // Adjust OrderPosition, so that the code gets saved in the order.
        if (empty($orderPosition->ArticleDescription)) {
            $description = '';
        } else {
            $description = $orderPosition->ArticleDescription."\n\n"."Der Gutschein-Code lautet: ".$giftVoucher->code;
        }
        $orderPosition->setField('ArticleDescription', $description);
    }
}