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
class SilvercartAutoVoucherGenerator extends DataObject {

    /**
     * DB attributes.
     *
     * @var array
     */
    public static $db = array(
        'Title'                    => 'Varchar(64)',
        'ValidFrom'                => 'SS_DateTime',
        'ValidUntil'               => 'SS_DateTime',
        'AlwaysValid'              => 'Boolean',
        'UseURLParamForActivation' => 'Boolean',
        'URLParamForActivation'    => 'Varchar(64)',
    );
    
    /**
     * Has many relations.
     *
     * @var array
     */
    public static $has_many = array(
        'SilvercartAutoVoucherGeneratorRules' => 'SilvercartAutoVoucherGeneratorRule',
    );
    
    /**
     * Defaults for DB attributes.
     *
     * @var array
     */
    public static $defaults = array(
        'AlwaysValid' => false,
    );
    
    /**
     * Defaults for DB attributes.
     *
     * @var array
     */
    public static $casting = array(
        'ValidFromNice'   => 'Text',
        'ValidUntilNice'  => 'Text',
        'AlwaysValidNice' => 'Text',
        'RulesCount'      => 'Int',
    );
    
    /**
     * Determines whether there is an auto voucher for the current cart.
     *
     * @var bool
     */
    public static $has_voucher = false;
    
    /**
     * Voucher rule
     *
     * @var ArrayData 
     */
    public static $rule = null;
    
    /**
     * Voucher data
     *
     * @var ArrayData 
     */
    public static $voucher = null;
    
    /**
     * Determines whether there is a NEXT auto voucher for the current cart.
     *
     * @var bool
     */
    public static $has_next_voucher = false;
    
    /**
     * NEXT voucher rule
     *
     * @var ArrayData 
     */
    public static $next_rule = null;
    
    /**
     * NEXT voucher data
     *
     * @var ArrayData 
     */
    public static $next_voucher = null;
    
    /**
     * Valid auto voucher generator
     *
     * @var SilvercartAutoVoucherGenerator 
     */
    public static $valid_generator = null;
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
                    'Title'                               => _t('SilvercartAutoVoucherGenerator.Title'),
                    'ValidFrom'                           => _t('SilvercartAutoVoucherGenerator.ValidFrom'),
                    'ValidUntil'                          => _t('SilvercartAutoVoucherGenerator.ValidUntil'),
                    'AlwaysValid'                         => _t('SilvercartAutoVoucherGenerator.AlwaysValid'),
                    'UseURLParamForActivation'            => _t('SilvercartAutoVoucherGenerator.UseURLParamForActivation'),
                    'URLParamForActivation'               => _t('SilvercartAutoVoucherGenerator.URLParamForActivation'),
                    'URLParamForActivationInfo'           => _t('SilvercartAutoVoucherGenerator.URLParamForActivationInfo'),
                    'SilvercartAutoVoucherGeneratorRules' => _t('SilvercartAutoVoucherGeneratorRule.PLURALNAME'),
                )
        );
        $this->extend('updateFieldLabels', $labels);
        return $labels;
    }
    
    /**
     * Returns the CMS fields.
     * 
     * @param array $params Scaffolding params
     * 
     * @return FieldSet
     */
    public function getCMSFields($params = null) {
        $fields = parent::getCMSFields($params);
        
        $validFromField  = $fields->dataFieldByName('ValidFrom');
        $validUntilField = $fields->dataFieldByName('ValidUntil');
        
        /* @var $validFromField DatetimeField */
        /* @var $validUntilField DatetimeField */
        
        $validFromField->setLocale(i18n::get_locale());
        $validFromField->getDateField()->setConfig('showcalendar', true);
        $validFromField->getTimeField()->setConfig('showdropdown', true);
        
        $validUntilField->setLocale(i18n::get_locale());
        $validUntilField->getDateField()->setConfig('showcalendar', true);
        $validUntilField->getTimeField()->setConfig('showdropdown', true);
        
        $fields->dataFieldByName('URLParamForActivation')->setRightTitle($this->fieldLabel('URLParamForActivationInfo'));
        
        return $fields;
    }
    
    /**
     * Summary fields.
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.11.2013
     */
    public function summaryFields() {
        $summaryFields = array(
            'Title'           => $this->fieldLabel('Title'),
            'ValidFromNice'   => $this->fieldLabel('ValidFrom'),
            'ValidUntilNice'  => $this->fieldLabel('ValidUntil'),
            'AlwaysValidNice' => $this->fieldLabel('AlwaysValid'),
            'RulesCount'      => $this->fieldLabel('SilvercartAutoVoucherGeneratorRules'),
        );
        
        $this->extend('updateSummaryFields', $summaryFields);
        
        return $summaryFields;
    }
    
    /**
     * Returns the valid from date in a nice format.
     * 
     * @return string
     */
    public function getValidFromNice() {
        $validFromNice = '---';
        
        if (!is_null($this->ValidFrom)) {
            $date = new Date();
            $date->setValue($this->ValidFrom);
            $validFromNice = $date->Nice();
        }
        
        return $validFromNice;
    }
    
    /**
     * Returns the valid until date in a nice format.
     * 
     * @return string
     */
    public function getValidUntilNice() {
        $validUntilNice = '---';
        
        if (!is_null($this->ValidUntil)) {
            $date = new Date();
            $date->setValue($this->ValidUntil);
            $validUntilNice = $date->Nice();
        }
        
        return $validUntilNice;
    }
    
    /**
     * Returns the always valid value in a human readable format (i18n).
     * 
     * @return string
     */
    public function getAlwaysValidNice() {
        $alwaysValidNice = _t('Boolean.NO');
        
        if ($this->AlwaysValid) {
            $alwaysValidNice = _t('Boolean.YES');
        }
        
        return $alwaysValidNice;
    }
    
    /**
     * Returns the count of related rules.
     * 
     * @return int
     */
    public function getRulesCount() {
        return $this->SilvercartAutoVoucherGeneratorRules()->Count();
    }
    
    /**
     * Returns the current voucher generator rule data if exists.
     * 
     * @return ArrayData
     */
    public function getCurrentRule() {
        $rule = self::get_rule();
        return $rule;
    }
    
    /**
     * Returns the current voucher data if exists.
     * 
     * @return ArrayData
     */
    public function getCurrentVoucher() {
        $voucher = self::get_voucher();
        return $voucher;
    }
    
    /**
     * Returns the next voucher generator rule data if exists.
     * 
     * @return ArrayData
     */
    public function getNextRule() {
        $rule = self::get_next_rule();
        return $rule;
    }
    
    /**
     * Returns the next voucher data if exists.
     * 
     * @return ArrayData
     */
    public function getNextVoucher() {
        $voucher = self::get_next_voucher();
        return $voucher;
    }
    
    /**
     * Returns the description for the auto voucher position.
     * 
     * @return string
     */
    public function getPositionDescription() {
        $rule    = $this->getCurrentRule();
        $voucher = $this->getCurrentVoucher();
        
        switch ($rule->Type) {
            case 'Natural':
                $voucherText = $voucher->NaturalProductTitle;
                break;
            case 'Absolute':
            case 'Relative':
                $voucherText = $voucher->Amount . ' ' . $voucher->Currency;
                break;
            default:
                break;
        }
        
        return sprintf(
                _t('SilvercartAutoVoucherGenerator.CartPosition' . $rule->Type),
                $voucherText
        );
    }
    
    /**
     * Returns the description for the auto voucher position.
     * 
     * @return string
     */
    public function getNextPositionDescription() {
        $rule    = $this->getNextRule();
        $voucher = $this->getNextVoucher();
        
        switch ($rule->Type) {
            case 'Natural':
                $voucherText = $voucher->NaturalProductTitle;
                break;
            case 'Absolute':
            case 'Relative':
                $voucherText = $voucher->Amount . ' ' . $voucher->Currency;
                break;
            default:
                break;
        }
        
        return sprintf(
                _t('SilvercartAutoVoucherGenerator.NextCartPosition' . $rule->Type),
                $rule->MinimumOrderAmountNice,
                $voucherText
        );
    }

    /**
     * Generates a voucher.
     * 
     * @return SilvercartAbsoluteRebateVoucher
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 02.12.2013
     */
    public function generateVoucher() {
        $voucher        = null;
        $currentVoucher = $this->getCurrentVoucher();
        if ($currentVoucher != false) {
            
            $registeredCustomer = SilvercartCustomer::currentRegisteredCustomer();
            
            $value = new Money();
            $value->setAmount($currentVoucher->Amount);
            $value->setCurrency($currentVoucher->Currency);
            
            $voucher = new SilvercartAbsoluteRebateVoucher();
            $voucher->code                     = SilvercartVoucher::generate_code();
            $voucher->isActive                 = true;
            $voucher->minimumShoppingCartValue = 0;
            $voucher->maximumShoppingCartValue = 0;
            $voucher->quantity                 = 1;
            $voucher->quantityRedeemed         = 0;
            $voucher->ProductNumber            = '';
            $voucher->value                    = $value;
            $voucher->write();
            
            if ($registeredCustomer instanceof Member) {
                $voucher->RestrictToMember()->add($registeredCustomer);
            }
        }
        return $voucher;
    }
    
    /**
     * Checks whether the current URL params contain the required one.
     * 
     * @return boolean
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 03.12.2013
     */
    public function matchesWithCurrentURLParams() {
        $match = false;
        if ($this->UseURLParamForActivation &&
            !empty($this->URLParamForActivation)) {
            $match = self::matches_with_url_param($this->URLParamForActivation);
        }
        return $match;
    }
    
    /**
     * Checks whether the current URL params contain the required one.
     * 
     * @param string $param Param to match
     * 
     * @return boolean
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 03.12.2013
     */
    public static function matches_with_url_param($param) {
        $match = false;
        if (!empty($param)) {
            $value = '';
            if (strpos($param, '=') !== false) {
                list($param,$value) = explode('=', $param);
            }
            
            if (array_key_exists($param, $_GET)) {
                if (!empty($value)) {
                    if ($_GET[$param] == $value) {
                        $match = true;
                    }
                } else {
                    $match = true;
                }
            }
        }
        return $match;
    }

    /**
     * Returns the valid voucher generator.
     * 
     * @return SilvercartAutoVoucherGenerator
     */
    public static function get_valid_generator() {
        if (is_null(self::$valid_generator)) {
            self::$valid_generator = false;
            $generator = self::get_one_by_current_url_params();
            if ($generator == false) {
                $generator = DataObject::get_one(
                        'SilvercartAutoVoucherGenerator',
                        sprintf(
                                '(\'%s\' BETWEEN "ValidFrom" AND "ValidUntil" OR "AlwaysValid" = 1) AND "UseURLParamForActivation" = 0',
                                date('Y-m-d H:i:s')
                        )
                );
            }
            if ($generator instanceof SilvercartAutoVoucherGenerator) {
                self::$valid_generator = $generator;
            }
        }
        return self::$valid_generator;
    }
    
    /**
     * Sets a URL param based voucher campaign if exists and matches.
     * 
     * @return DataObjectSet
     */
    public static function set_by_current_url_params() {
        $urlParamTriggeredAutoVoucherGenerator   = false;
        $urlParamTriggeredAutoVoucherGeneratorID = Session::get('SilvercartAutoVoucherGenerator.TriggeredByURLParam');
        $URLParamMap                             = Session::get('SilvercartAutoVoucherGenerator.URLParamMap');
        if (is_null($urlParamTriggeredAutoVoucherGeneratorID)) {
            $urlParamTriggeredAutoVoucherGeneratorID = 0;
            $generators = DataObject::get(
                    'SilvercartAutoVoucherGenerator',
                    sprintf(
                            '(\'%s\' BETWEEN "ValidFrom" AND "ValidUntil" OR "AlwaysValid" = 1) AND "UseURLParamForActivation" = 1',
                            date('Y-m-d H:i:s')
                    )
            );
            if ($generators instanceof DataObjectSet) {
                $URLParamMap = $generators->map('URLParamForActivation', 'ID');
                foreach ($generators as $generator) {
                    if ($generator->matchesWithCurrentURLParams()) {
                        $urlParamTriggeredAutoVoucherGenerator   = $generator;
                        $urlParamTriggeredAutoVoucherGeneratorID = $generator->ID;
                        break;
                    }
                }
            }
            
            Session::set('SilvercartAutoVoucherGenerator.URLParamMap', $URLParamMap);
            Session::set('SilvercartAutoVoucherGenerator.TriggeredByURLParam', $urlParamTriggeredAutoVoucherGeneratorID);
            Session::save();
        } elseif (is_array($URLParamMap)) {
            foreach ($URLParamMap as $URLParam => $generatorID) {
                if (self::matches_with_url_param($URLParam)) {
                    $urlParamTriggeredAutoVoucherGenerator   = DataObject::get_by_id('SilvercartAutoVoucherGenerator', $generatorID);
                    $urlParamTriggeredAutoVoucherGeneratorID = $generatorID;
                    Session::set('SilvercartAutoVoucherGenerator.TriggeredByURLParam', $urlParamTriggeredAutoVoucherGeneratorID);
                    Session::save();
                    break;
                }
            }
        }
        return $urlParamTriggeredAutoVoucherGenerator;
    }
    
    /**
     * Returns the current URL param base voucher campaign.
     * 
     * @return SilvercartAutoVoucherGenerator
     */
    public static function get_one_by_current_url_params() {
        $urlParamTriggeredAutoVoucherGenerator   = false;
        $urlParamTriggeredAutoVoucherGeneratorID = Session::get('SilvercartAutoVoucherGenerator.TriggeredByURLParam');
        if (is_null($urlParamTriggeredAutoVoucherGeneratorID)) {
            $urlParamTriggeredAutoVoucherGenerator = self::set_by_current_url_params();
        } elseif ($urlParamTriggeredAutoVoucherGeneratorID > 0) {
            $urlParamTriggeredAutoVoucherGenerator = DataObject::get_by_id('SilvercartAutoVoucherGenerator', $urlParamTriggeredAutoVoucherGeneratorID);
        }
        
        return $urlParamTriggeredAutoVoucherGenerator;
    }

    /**
     * Returns whether there is an auto voucher for the current cart.
     * 
     * @return bool
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.11.2013
     */
    public static function has_voucher() {
        self::get_voucher();
        return self::$has_voucher;
    }
    
    /**
     * Returns the auto voucher rule for the current cart.
     * 
     * @return ArrayData
     */
    public static function get_rule() {
        if (is_null(self::$rule)) {
            self::$rule = false;
            if (Member::currentUser() instanceof Member &&
                Member::currentUser()->getCart() instanceof SilvercartShoppingCart) {
                $generator = self::get_valid_generator();
                if ($generator != false) {
                    $cart = Member::currentUser()->getCart();
                    /* @var $cart SilvercartShoppingCart */
                    $totalAmount = $cart->getAmountTotalWithoutFees();
                    /* @var $totalAmount Money */
                    $sql = sprintf(
                            'SELECT "VoucherValue","VoucherType","MinimumOrderAmountAmount","NaturalProductTitle","NaturalProductNumber" FROM "SilvercartAutoVoucherGeneratorRule" WHERE "SilvercartAutoVoucherGeneratorID" = %s AND "MinimumOrderAmountAmount" <= %s ORDER BY "MinimumOrderAmountAmount" DESC LIMIT 0,1',
                            $generator->ID,
                            $totalAmount->getAmount()
                    );
                    $results = DB::query($sql);
                    if ($results->numRecords() > 0) {
                        $rule = $results->first();
                        
                        $min = new Money();
                        $min->setAmount($rule['MinimumOrderAmountAmount']);
                        $min->setCurrency($totalAmount->getCurrency());
                        
                        $current_rule = new ArrayData(
                                array(
                                    'Value'                  => $rule['VoucherValue'],
                                    'Type'                   => $rule['VoucherType'],
                                    'MinimumOrderAmountNice' => $min->Nice(),
                                    'NaturalProductTitle'    => $rule['NaturalProductTitle'],
                                    'NaturalProductNumber'   => $rule['NaturalProductNumber'],
                                )
                        );

                        self::$rule = $current_rule;
                    }
                }
                

            }
        }
        return self::$rule;
    }
    
    /**
     * Returns the auto voucher for the current cart.
     * 
     * @return ArrayData
     */
    public static function get_voucher() {
        if (is_null(self::$voucher)) {
            self::$voucher = false;
            $rule = self::get_rule();

            if ($rule != false) {
                $cart = Member::currentUser()->getCart();
                /* @var $cart SilvercartShoppingCart */
                $totalAmount = $cart->getAmountTotalWithoutFees();
                /* @var $totalAmount Money */
                
                $price = new Money();
                $price->setAmount(0);
                $price->setCurrency($totalAmount->getCurrency());

                $voucher = new ArrayData(
                        array(
                            'Value'                => $rule->Value,
                            'Unit'                 => $rule->Type == 'Absolute' ? $totalAmount->getCurrency() : '%',
                            'Amount'               => $rule->Type == 'Absolute' ? $rule->Value : round(($totalAmount->getAmount() / 100) * $rule->Value, 2),
                            'Currency'             => $totalAmount->getCurrency(),
                            'NaturalProductTitle'  => $rule->NaturalProductTitle,
                            'NaturalProductNumber' => $rule->NaturalProductNumber,
                            'PriceNice'            => $price->Nice(),
                            'IsNatural'            => $rule->Type == 'Natural' ? true : false,
                        )
                );

                self::$has_voucher = true;
                self::$voucher     = $voucher;
            }
        }
        return self::$voucher;
    }

    /**
     * Returns whether there is a NEXT auto voucher for the current cart.
     * 
     * @return bool
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.11.2013
     */
    public static function has_next_voucher() {
        self::get_next_voucher();
        return self::$has_next_voucher;
    }
    
    /**
     * Returns the NEXT auto voucher rule for the current cart.
     * 
     * @return ArrayData
     */
    public static function get_next_rule() {
        if (is_null(self::$next_rule)) {
            self::$next_rule = false;
            if (Member::currentUser() instanceof Member &&
                Member::currentUser()->getCart() instanceof SilvercartShoppingCart) {
                $generator = self::get_valid_generator();
                if ($generator != false) {
                    $cart = Member::currentUser()->getCart();
                    /* @var $cart SilvercartShoppingCart */
                    $totalAmount = $cart->getAmountTotalWithoutFees();
                    /* @var $totalAmount Money */
                    $sql = sprintf(
                            'SELECT "VoucherValue","VoucherType","MinimumOrderAmountAmount","NaturalProductTitle","NaturalProductNumber" FROM "SilvercartAutoVoucherGeneratorRule" WHERE "SilvercartAutoVoucherGeneratorID" = %s AND "MinimumOrderAmountAmount" > %s ORDER BY "MinimumOrderAmountAmount" ASC LIMIT 0,1',
                            $generator->ID,
                            $totalAmount->getAmount()
                    );
                    $results = DB::query($sql);
                    if ($results->numRecords() > 0) {
                        $rule = $results->first();
                        
                        $min = new Money();
                        $min->setAmount($rule['MinimumOrderAmountAmount']);
                        $min->setCurrency($totalAmount->getCurrency());
                        
                        $next_rule = new ArrayData(
                                array(
                                    'Value'                  => $rule['VoucherValue'],
                                    'Type'                   => $rule['VoucherType'],
                                    'MinimumOrderAmountNice' => $min->Nice(),
                                    'NaturalProductTitle'    => $rule['NaturalProductTitle'],
                                    'NaturalProductNumber'   => $rule['NaturalProductNumber'],
                                )
                        );

                        self::$next_rule = $next_rule;
                    }
                }
                

            }
        }
        return self::$next_rule;
    }
    
    /**
     * Returns the NEXT auto voucher for the current cart.
     * 
     * @return ArrayData
     */
    public static function get_next_voucher() {
        if (is_null(self::$next_voucher)) {
            self::$next_voucher = false;
            $rule = self::get_next_rule();

            if ($rule != false) {
                $cart = Member::currentUser()->getCart();
                /* @var $cart SilvercartShoppingCart */
                $totalAmount = $cart->getAmountTotalWithoutFees();
                /* @var $totalAmount Money */
                
                $price = new Money();
                $price->setAmount(0);
                $price->setCurrency($totalAmount->getCurrency());
                
                $voucher = new ArrayData(
                        array(
                            'Value'                => $rule->Value,
                            'Unit'                 => $rule->Type == 'Absolute' ? $totalAmount->getCurrency() : '%',
                            'Amount'               => $rule->Type == 'Absolute' ? $rule->Value : round(($totalAmount->getAmount() / 100) * $rule->Value, 2),
                            'Currency'             => $totalAmount->getCurrency(),
                            'NaturalProductTitle'  => $rule->NaturalProductTitle,
                            'NaturalProductNumber' => $rule->NaturalProductNumber,
                            'PriceNice'            => $price->Nice(),
                            'IsNatural'            => $rule->Type == 'Natural' ? true : false,
                        )
                );

                self::$has_next_voucher = true;
                self::$next_voucher     = $voucher;
            }
        }
        return self::$next_voucher;
    }
    
}