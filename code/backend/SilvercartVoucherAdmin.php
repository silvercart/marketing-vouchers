<?php
/**
 * Copyright 2011 pixeltricks GmbH
 *
 * This file is part of SilverCart.
 *
 * SilverCart is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SilverCart is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with SilverCart.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Voucher administration panel.
 *
 * @package Silvercart
 * @subpackage Vouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 21.01.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartVoucherAdmin extends ModelAdmin {

    /**
     * The code of the menu under which this admin should be shown.
     * 
     * @var string
     */
    public static $menuCode = 'products';

    /**
     * The section of the menu under which this admin should be grouped.
     * 
     * @var string
     */
    public static $menuSortIndex = 150;

    /**
     * The URL segment
     *
     * @var string
     */
    public static $url_segment = 'silvercart-vouchers';

    /**
     * The menu title
     *
     * @var string
     */
    public static $menu_title  = 'Vouchers';
    
    /**
     * Managed models
     *
     * @var array
     */
    public static $managed_models = array(
        'SilvercartAbsoluteRebateVoucher' => array(
            'collection_controller' => 'SilvercartVoucherAdmin_CollectionController'
        ),
        'SilvercartRelativeRebateVoucher' => array(
            'collection_controller' => 'SilvercartVoucherAdmin_CollectionController'
        )
    );
    
    /**
     * Set the translations.
     * 
     * @return void
     * 
     * @author Sascha Koehler <skoehler@pixeltricks.de>, Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.08.2012
     */
    public function __construct() {
        self::$menu_title = _t('SilvercartVoucherAdmin.TITLE');
        parent::__construct();
    }
}
/**
 * Voucher administration panel.
 *
 * @package Silvercart
 * @subpackage Vouchers
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 21.01.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartVoucherAdmin_CollectionController extends ModelAdmin_CollectionController {

    /**
     * Fix the write routine.
     *
     * @param array          $data    The data
     * @param Form           $form    The form object
     * @param SS_HTTPRequest $request The request object
     *
     * @return SS_HTTPResponse
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2012 pixeltricks GmbH
     * @since 19.07.2012
     */
    public function doCreate($data, $form, $request) {
        $className = $this->getModelClass();
        $model = new $className();
        // We write before saveInto, since this will let us save has-many and many-many relationships :-)
        $form->saveInto($model);
        $model->write();
        $form->saveInto($model);

        $model->write();

        if (Director::is_ajax()) {
            $class = $this->parentController->getRecordControllerClass($this->getModelClass());
            $recordController = new $class($this, $request, $model->ID);
            return new SS_HTTPResponse(
                $recordController->EditForm()->forAjaxTemplate(),
                200,
                sprintf(
                    _t('ModelAdmin.LOADEDFOREDITING', "Loaded '%s' for editing."),
                    $model->Title
                )
            );
        } else {
            Director::redirect(Controller::join_links($this->Link(), $model->ID , 'edit'));
        }
    }
}