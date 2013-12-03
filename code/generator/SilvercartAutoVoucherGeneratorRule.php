<?php
/**
 * Copyright 2013 pixeltricks GmbH
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
 * Voucher generator.
 *
 * @package Silvercart
 * @subpackage Vouchers
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 21.11.2013
 * @copyright 2013 pixeltricks GmbH
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartAutoVoucherGeneratorRule extends DataObject {

    /**
     * DB attributes.
     *
     * @var array
     */
    public static $db = array(
        'MinimumOrderAmount'   => 'Money',
        'VoucherValue'         => 'Float',
        'VoucherType'          => 'Enum("Absolute,Relative,Natural","Absolute")',
        'NaturalProductNumber' => 'Varchar(50)',
        'NaturalProductTitle'  => 'Varchar(255)',
    );
    
    /**
     * Has one relations.
     *
     * @var array
     */
    public static $has_one = array(
        'SilvercartAutoVoucherGenerator' => 'SilvercartAutoVoucherGenerator',
    );
    
    /**
     * Casted attributes.
     *
     * @var array
     */
    public static $casting = array(
        'MinimumOrderAmountNice' => 'Text',
        'VoucherSummary'         => 'Text',
    );
    
    /**
     * Singular name.
     * 
     * @return string
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.11.2013
     */
    public function singular_name() {
        return SilvercartTools::singular_name_for($this);
    }
    
    /**
     * Plural name.
     * 
     * @return string
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.11.2013
     */
    public function plural_name() {
        return SilvercartTools::plural_name_for($this);
    }
    
    /**
     * Returns the field labels.
     * 
     * @param bool $includerelations Include relations?
     * 
     * @return array
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.11.2013
     */
    public function fieldLabels($includerelations = true) {
        $labels = array_merge(
                parent::fieldLabels($includerelations),
                array(
                    'MinimumOrderAmount'             => _t('SilvercartAutoVoucherGeneratorRule.MinimumOrderAmount'),
                    'VoucherValue'                   => _t('SilvercartAutoVoucherGeneratorRule.VoucherValue'),
                    'VoucherType'                    => _t('SilvercartAutoVoucherGeneratorRule.VoucherType'),
                    'NaturalProductNumber'           => _t('SilvercartAutoVoucherGeneratorRule.NaturalProductNumber'),
                    'NaturalProductTitle'            => _t('SilvercartAutoVoucherGeneratorRule.NaturalProductTitle'),
                    'SilvercartAutoVoucherGenerator' => _t('SilvercartAutoVoucherGenerator.SINGULARNAME'),
                )
        );
        $this->extend('updateFieldLabels', $labels);
        return $labels;
    }
    
    /**
     * Returns the CMS fields
     * 
     * @param array $params Scaffolding params.
     * 
     * @return FieldSet
     */
    public function getCMSFields($params = null) {
        $fields = parent::getCMSFields(
                array_merge(
                    array(
                        'fieldClasses' => array(
                            'MinimumOrderAmount' => 'SilvercartMoneyField',
                        ),
                    ),
                    (array)$params
                )
        );
        
        if ($this->VoucherType != 'Natural') {
            $fields->removeByName('NaturalProductNumber');
            $fields->removeByName('NaturalProductTitle');
        } else {
            $fields->removeByName('VoucherValue');
        }
        
        return $fields;
    }
    
    /**
     * Returns the summary fields
     * 
     * @return array
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.11.2013
     */
    public function summaryFields() {
        $summaryFields = array(
            'MinimumOrderAmountNice' => $this->fieldLabel('MinimumOrderAmount'),
            'VoucherSummary'         => $this->fieldLabel('VoucherType'),
        );
        $this->extend('updateSummaryFields', $summaryFields);
        return $summaryFields;
    }
    
    /**
     * Returns the MinimumOrderAmount in a nice format.
     * 
     * @return string
     */
    public function getMinimumOrderAmountNice() {
        return $this->MinimumOrderAmount->Nice();
    }
    
    /**
     * Returns the voucher summary to display in backend.
     * 
     * @return string
     */
    public function getVoucherSummary() {
        $voucherSummary = '';
        
        switch ($this->VoucherType) {
            case 'Natural':
                $voucherSummary = $this->NaturalProductNumber . ' - ' . $this->NaturalProductTitle;
                break;
            case 'Absolute':
                $voucherSummary = $this->VoucherValue . ' ' . SilvercartConfig::DefaultCurrencySymbol();
                break;
            case 'Relative':
                $voucherSummary = $this->VoucherValue . '%';
                break;
            default:
                break;
        }
        
        return $voucherSummary;
    }
    
}