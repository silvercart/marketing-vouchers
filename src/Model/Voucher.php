<?php

namespace SilverCart\Voucher\Model;

use SilverCart\Admin\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverCart\Model\Customer\Customer;
use SilverCart\Model\Order\Order;
use SilverCart\Model\Order\ShoppingCart;
use SilverCart\Model\Order\ShoppingCartPositionNotice;
use SilverCart\Model\Pages\ProductGroupPage;
use SilverCart\Model\Product\Product;
use SilverCart\Model\Product\Tax;
use SilverCart\ORM\DataObjectExtension;
use SilverCart\ORM\FieldType\DBMoney;
use SilverCart\Voucher\Security\VoucherValidator;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\HiddenField;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBMoney as SilverStripeDBMoney;
use SilverStripe\ORM\Filters\ExactMatchFilter;
use SilverStripe\ORM\Filters\PartialMatchFilter;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\View\ArrayData;

/**
 * Basic voucher class.
 *
 * @package SilverCart
 * @subpackage Voucher\Model
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 14.05.2020
 * @copyright 2020 pixeltricks GmbH
 * @license see license file in modules root directory
 * 
 * @param string  $code                     Code
 * @param bool    $isActive                 is active
 * @param DBMoney $minimumShoppingCartValue minimum shopping cart value
 * @param DBMoney $maximumShoppingCartValue maximum shopping cart value
 * @param int     $quantity                 quantity
 * @param int     $quantityRedeemed         quantity redeemed
 * @param string  $ProductNumber            Product number
 * 
 * @method \SilverStripe\ORM\ManyManyList RestrictToMember()        Returns a list of related members to restrict this voucher to.
 * @method \SilverStripe\ORM\ManyManyList RestrictToGroup()         Returns a list of related groups to restrict this voucher to.
 * @method \SilverStripe\ORM\ManyManyList RestrictToProductGroups() Returns a list of related product groups to restrict this voucher to.
 * @method \SilverStripe\ORM\ManyManyList RestrictToProducts()      Returns a list of related products to restrict this voucher to.
 * @method \SilverStripe\ORM\ManyManyList VoucherHistory()          Returns a list of related voucher history objects.
 * @method \SilverStripe\ORM\ManyManyList Members()                 Returns a list of related members.
 */
class Voucher extends DataObject implements PermissionProvider
{
    use \SilverCart\ORM\ExtensibleDataObject;
    
    const PERMISSION_CREATE = 'SILVERCART_VOUCHER_CREATE';
    const PERMISSION_DELETE = 'SILVERCART_VOUCHER_DELETE';
    const PERMISSION_EDIT   = 'SILVERCART_VOUCHER_EDIT';
    const PERMISSION_VIEW   = 'SILVERCART_VOUCHER_VIEW';
    
    /**
     * Returns a voucher with the given $code.
     * 
     * @param string $code Voucher code
     * 
     * @return Voucher|null
     */
    public static function getByCode(string $code) : ?Voucher
    {
        return Voucher::get()->filter('code', $code)->first();
    }

    /**
     * Generates a single voucher code.
     * 
     * @return string
     */
    public static function generateCode() : string
    {
        $parts = [];
        for ($i = 0; $i < self::config()->generator_code_part_count; $i++) {
            $part = '';
            for ($j = 0; $j < self::config()->generator_code_part_length; $j++) {
                $part .= strtoupper(dechex(rand(0,15)));
            }
            $parts[] = $part;
        }
        $code = implode(self::config()->generator_code_part_delimiter, $parts);
        if (self::getByCode($code) instanceof Voucher) {
            $code = self::generateCode();
        }
        return $code;
    }
    
    /**
     * Generates voucher codes.
     * 
     * @param int $count Count of voucher codes to generate
     * 
     * @return string[]
     */
    public static function generateCodes(int $count = 1) : array
    {
        $codes = [];
        for ($x = 0; $x < $count; $x++) {
            $codes[] = self::generateCode();
        }
        return $codes;
    }
    
    /**
     * Amount of code parts when using the generator.
     *
     * @var int
     */
    private static $generator_code_part_count = 4;
    /**
     * Delimiter to use between the code parts when using the generator.
     *
     * @var string
     */
    private static $generator_code_part_delimiter = '-';
    /**
     * Length of a single code part when using the generator.
     *
     * @var int
     */
    private static $generator_code_part_length = 5;
    /**
     * Table name
     *
     * @var string
     */
    private static $table_name = 'SilvercartVoucher';
    /**
     * Attributes.
     *
     * @var array
     */
    private static $db = [
        'code'                     => 'Varchar(50)',
        'isActive'                 => 'Boolean',
        'minimumShoppingCartValue' => DBMoney::class,
        'maximumShoppingCartValue' => DBMoney::class,
        'quantity'                 => 'Int',
        'quantityRedeemed'         => 'Int',
        'ProductNumber'            => 'Varchar(50)',
    ];
    /**
     * 1:1 relations
     *
     * @var array
     */
    private static $has_one = [
        'Tax' => Tax::class,
    ];
    /**
     * Has-many Relationships.
     *
     * @var array
     */
    private static $has_many = [
        'VoucherHistory' => VoucherHistory::class,
    ];
    /**
     * Many-many Relationships.
     *
     * @var array
     */
    private static $many_many = [
        'RestrictToMember'        => Member::class,
        'RestrictToGroup'         => Group::class,
        'RestrictToProductGroups' => ProductGroupPage::class,
        'RestrictToProducts'      => Product::class,
    ];
    /**
     * Belongs-many-many Relationships.
     *
     * @var array
     */
    private static $belongs_many_many = [
        'Members' => Member::class,
    ];
    /**
     * Casted fields
     *
     * @var array
     */
    private static $casting = [
        'castedFormattedCreationDate' => 'VarChar(10)',
    ];
    /**
     * Config property to enable or disable the voucher module.
     *
     * @var bool
     */
    private static $enable_voucher_module = true;
    /**
     * A list of already checked shopping cart amounts
     *
     * @var array
     */
    protected $isShoppingCartAmountValid = [];
    /**
     * A list of already checked shopping cart positions
     *
     * @var array
     */
    protected $isValidForShoppingCartItems = [];

    /**
     * Set permissions.
     *
     * @return array
     */
    public function providePermissions() : array
    {
        $permissions = [
            self::PERMISSION_VIEW   => [
                'name'     => $this->fieldLabel(self::PERMISSION_VIEW),
                'help'     => $this->fieldLabel(self::PERMISSION_VIEW . '_HELP'),
                'category' => $this->i18n_singular_name(),
                'sort'     => 10,
            ],
            self::PERMISSION_CREATE   => [
                'name'     => $this->fieldLabel(self::PERMISSION_CREATE),
                'help'     => $this->fieldLabel(self::PERMISSION_CREATE . '_HELP'),
                'category' => $this->i18n_singular_name(),
                'sort'     => 20,
            ],
            self::PERMISSION_EDIT   => [
                'name'     => $this->fieldLabel(self::PERMISSION_EDIT),
                'help'     => $this->fieldLabel(self::PERMISSION_EDIT . '_HELP'),
                'category' => $this->i18n_singular_name(),
                'sort'     => 20,
            ],
            self::PERMISSION_DELETE => [
                'name'     => $this->fieldLabel(self::PERMISSION_DELETE),
                'help'     => $this->fieldLabel(self::PERMISSION_DELETE . '_HELP'),
                'category' => $this->i18n_singular_name(),
                'sort'     => 30,
            ],
        ];
        $this->extend('updateProvidePermissions', $permissions);
        return $permissions;
    }

    /**
     * Indicates wether the current user can view this object.
     * 
     * @param Member $member Member to check permission for
     *
     * @return bool
     */
    public function canView($member = null) : bool
    {
        return parent::canView($member)
            || Permission::checkMember($member, self::PERMISSION_VIEW);
    }
    
    /**
     * Order should not be created via backend
     * 
     * @param Member $member Member to check permission for
     *
     * @return bool
     */
    public function canCreate($member = null, $context = []) : bool
    {
        return parent::canCreate($member, $context)
            || Permission::checkMember($member, self::PERMISSION_CREATE);
    }

    /**
     * Indicates wether the current user can edit this object.
     * 
     * @param Member $member Member to check permission for
     *
     * @return bool
     */
    public function canEdit($member = null) : bool
    {
        return parent::canEdit($member)
            || Permission::checkMember($member, self::PERMISSION_EDIT);
    }

    /**
     * Indicates wether the current user can delete this object.
     * 
     * @param Member $member Member to check permission for
     *
     * @return bool
     */
    public function canDelete($member = null) : bool
    {
        return parent::canDelete($member)
            || Permission::checkMember($member, self::PERMISSION_DELETE);
    }

    /**
     * Field labels for display in tables.
     *
     * @param boolean $includerelations A boolean value to indicate if the labels returned include relation fields
     *
     * @return array
     */
    public function fieldLabels($includerelations = true) : array
    {
        return $this->defaultFieldLabels($includerelations, [
            'code'                        => _t(self::class . '.CODE', 'Code'),
            'isActive'                    => _t(self::class . '.ISACTIVE', 'Is active'),
            'minimumShoppingCartValue'    => _t(self::class . '.MINIMUM_SHOPPINGCART_VALUE', 'Minimum shopping cart value'),
            'maximumShoppingCartValue'    => _t(self::class . '.MAXIMUM_SHOPPINGCART_VALUE', 'Maximum shopping cart value'),
            'OriginalValue'               => _t(self::class . '.ORIGINAL_VALUE', 'Original value'),
            'quantity'                    => _t(self::class . '.QUANTITY', 'Quantity'),
            'quantityRedeemed'            => _t(self::class . '.QUANTITY_REDEEMED', 'Quantity redeemed'),
            'Tax'                         => Tax::singleton()->singular_name(),
            'Redeem'                      => _t(self::class . '.LABEL-REDEEM', 'Redeem '),
            'RedeemedVouchers'            => _t(self::class . '.REDEEMED_VOUCHERS', 'Redeemed vouchers: '),
            'RemainingCredit'             => _t(self::class . '.REMAINING_CREDIT', 'Remaining credit'),
            'RestrictToMember'            => _t(self::class . '.RESTRICT_TO_MEMBER', 'Restrict to customers'),
            'RestrictToGroup'             => _t(self::class . '.RESTRICT_TO_GROUP', 'Restrict to groups'),
            'RestrictToProductGroups'     => _t(self::class . '.RESTRICT_TO_PRODUCTGROUP', 'Restrict to product groups'),
            'RestrictToProducts'          => _t(self::class . '.RESTRICT_TO_PRODUCT', 'Restrict to products'),
            'VoucherHistory'              => VoucherHistory::singleton()->singular_name(),
            'castedFormattedCreationDate' => _t(self::class . '.CREATED', 'Created'),
            'ProductNumber'               => _t(self::class . '.PRODUCTNUMBER', 'Product number'),
            'ErrorCodeNotValid'           => _t(self::class . '.ERRORMESSAGE-CODE_NOT_VALID', 'This voucher code is not valid.'),
            'ErrorCustomerNotEligible'    => _t(self::class . '.ERRORMESSAGE-CUSTOMER_NOT_ELIGIBLE', 'You\'re not entitled to redeem this voucher.'),
            'ErrorNotRedeemable'          => _t(self::class . '.ERRORMESSAGE-NOT_REDEEMABLE', 'This voucher can\'t be redeemed.'),
            'ErrorAlreadyRedeemed'        => _t(self::class . '.ERRORMESSAGE-COMPLETELY_REDEEMED_ALREADY', 'This voucher is completely redeemed.'),
            'ErrorAlreadyInCart'          => _t(self::class . '.ERRORMESSAGE-ALREADY_IN_SHOPPINGCART', 'This voucher is already in your shoppingcart.'),
            'ErrorValueNotValid'          => _t(self::class . '.ERRORMESSAGE-SHOPPINGCARTVALUE_NOT_VALID', 'The shoppingcart value is not valid.'),
            'ErrorItemsNotValid'          => _t(self::class . '.ERRORMESSAGE-SHOPPINGCARTITEMS_NOT_VALID', 'Your cart doesn\'t contain the appropriate products for this voucher.'),
            'Value'                       => _t(self::class . '.VALUE', 'Value'),
            self::PERMISSION_CREATE           => _t(self::class . '.' . self::PERMISSION_CREATE, 'Create Vouchers'),
            self::PERMISSION_CREATE . '_HELP' => _t(self::class . '.' . self::PERMISSION_CREATE . '_HELP', 'Allows an user to create new vouchers.'),
            self::PERMISSION_VIEW             => _t(self::class . '.' . self::PERMISSION_VIEW, 'View Vouchers'),
            self::PERMISSION_VIEW . '_HELP'   => _t(self::class . '.' . self::PERMISSION_VIEW . '_HELP', 'Allows an user to view vouchers.'),
            self::PERMISSION_EDIT             => _t(self::class . '.' . self::PERMISSION_EDIT, 'Edit Vouchers'),
            self::PERMISSION_EDIT . '_HELP'   => _t(self::class . '.' . self::PERMISSION_EDIT . '_HELP', 'Allows an user to edit vouchers.'),
            self::PERMISSION_DELETE           => _t(self::class . '.' . self::PERMISSION_DELETE, 'Delete Vouchers'),
            self::PERMISSION_DELETE . '_HELP' => _t(self::class . '.' . self::PERMISSION_DELETE . '_HELP', 'Allows an user to delete vouchers.'),
        ]);
    }

    /**
     * Returns the summary fields for table overviews.
     *
     * @return array
     */
    public function summaryFields() : array
    {
        return [
            'castedFormattedCreationDate' => $this->fieldLabel('castedFormattedCreationDate'),
            'code'                        => $this->fieldLabel('code'),
            'isActive'                    => $this->fieldLabel('isActive'),
            'quantity'                    => $this->fieldLabel('quantity'),
            'quantityRedeemed'            => $this->fieldLabel('quantityRedeemed'),
        ];
    }

    /**
     * Returns the searchable fields.
     *
     * @return array
     */
    public function searchableFields() : array
    {
        $fields = [];
        $fields['code'] = [
            'title'  => $this->fieldLabel('code'),
            'filter' => PartialMatchFilter::class,
        ];
        $fields['quantity'] = [
            'title'  => $this->fieldLabel('quantity'),
            'filter' => PartialMatchFilter::class,
        ];
        $fields['isActive'] = [
            'title'  => $this->fieldLabel('isActive'),
            'filter' => ExactMatchFilter::class,
        ];
        return $fields;
    }
    
    /**
     * Returns the title
     * 
     * @return string
     */
    public function getTitle() : string
    {
        return "{$this->singular_name()} (Code: {$this->code})";
    }
    
    /**
     * Returns the related tax.
     * 
     * @return Tax
     */
    public function Tax() : Tax
    {
        $tax = $this->getComponent('Tax');
        if (!$tax->exists()) {
            $tax = Tax::getDefault();
        }
        return $tax;
    }

    /**
     * Returns a nicely formatted date that respects the local settings.
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 11.05.2011
     */
    public function castedFormattedCreationDate() : string
    {
        $old_locale = setlocale(LC_TIME, null);
        $new_locale = setlocale(LC_TIME, i18n::get_locale(), i18n::get_locale().'.utf8');
        $date       = strftime("%x %X", strtotime($this->Created));
        setlocale(LC_TIME, $old_locale);
        return $date;
    }

    /**
     * Initialisation
     *
     * @return void
     */
    public function init() : void
    {
        parent::init();
        $member = Customer::currentUser();
        if ($member instanceof Member) {
            $member->ShoppingCart()->registerModule($this);
        }
    }

    /**
     * Performs all checks to make sure, that this voucher is allowed in the
     * shopping cart. Returns an array with status and messages.
     *
     * Returns:
     * <code>
     * [
     *     'error'    => bool,
     *     'messages' => string[],
     * ]
     * </code>
     * 
     * @param Voucher      $voucher      the vouchers code
     * @param Member       $member       the member object to check against
     * @param ShoppingCart $shoppingCart the shopping cart to check against
     *
     * @return array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>,
     *         Patrick Schneider <pschneider@pixeltricks.de>,
     *         Sebastian Diel <sdiel@pixeltricks.de>
     * @since 14.05.2020
     */
    public function checkifAllowedInShoppingCart(Voucher $voucher, Member $member, ShoppingCart $shoppingCart) : array
    {
        $status   = $this->areShoppingCartConditionsMet($shoppingCart);
        $error    = $status['error'];
        $messages = $status['messages'];
        if (!$error) {
            $voucherCode = $voucher->code;
            $voucherID   = $voucher->ID;
            if (!$this->isCodeValid($voucherCode)) {
                $error      = true;
                $messages[] = $this->fieldLabel('ErrorCodeNotValid');
            } elseif (!$this->isCustomerEligible($member)) {
                $error      = true;
                $messages[] = $this->fieldLabel('ErrorCustomerNotEligible');
            } elseif (!$this->isRedeemable($member)) {
                $error      = true;
                $messages[] = $this->fieldLabel('ErrorNotRedeemable');
            } elseif ($this->isCompletelyRedeemedAlready($member, $voucherID)) {
                $error = true;
                $messages[] = $this->fieldLabel('ErrorAlreadyRedeemed');
            } elseif ($this->isInShoppingCartAlready($shoppingCart)) {
                $error      = true;
                $messages[] = $this->fieldLabel('ErrorAlreadyInCart');
            }
        }
        return [
            'error'    => $error,
            'messages' => $messages,
        ];
    }

    /**
     * This method gets called when converting the shoppingcart positions to
     * order positions.
     * Implement it in your own voucher types if needed.
     *
     * @param ShoppingCart         $shoppingCart         the shoppingcart object
     * @param ShoppingCartPosition $shoppingCartPosition shoppingcart position with voucher
     * @param Voucher              $originalVoucher      the original voucher
     * @param Member               $member               member object
     *
     * @return void
     */
    public function convert(ShoppingCart $shoppingCart, ShoppingCartPosition $shoppingCartPosition, Voucher $originalVoucher, Member $member) : void
    {
        // Implement in descendants
    }

    /**
     * Performs checks related to the shopping cart entries to ensure that
     * the voucher is allowed to be placed in the cart.
     * If the conditions are not met the voucher is removed from the cart.
     *
     * @param ShoppingCart $shoppingCart                 the shopping cart to check against
     * @param Member       $member                       the shopping cart to check against
     * @param array        $excludeShoppingCartPositions Positions that shall not be counted
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>,
     *         Sebastian Diel <sdiel@pixeltricks.de>
     * @since 14.05.2020
     */
    public function performShoppingCartConditionsCheck(ShoppingCart $shoppingCart, Member $member, array $excludeShoppingCartPositions = []) : void
    {
        if ($this->isInDB()) {
            if (!empty($excludeShoppingCartPositions)
             && in_array($this->ID, $excludeShoppingCartPositions)
            ) {
                return;
            }
            if (!$this->isRedeemable()) {
                ShoppingCartPositionNotice::addAllowedNotice('voucher-invalid', _t(self::class . '.VocherInvalid', 'The voucher {code} is no more valid and was removed from your shopping cart.', ['code' => $this->code]), ShoppingCartPositionNotice::NOTICE_TYPE_DANGER, ShoppingCartPositionNotice::NOTICE_FA_EXCLAMATION_CIRCLE);
                ShoppingCartPositionNotice::setNotice(0, 'voucher-invalid');
                $position = ShoppingCartPosition::getVoucherShoppingCartPosition($shoppingCart->ID, $this->ID);
                if ($position instanceof ShoppingCartPosition) {
                    $position->delete();
                    VoucherHistory::create()->add($this, $member, 'removed');
                }
            } else {
                $status   = $this->areShoppingCartConditionsMet($shoppingCart);
                $position = ShoppingCartPosition::getVoucherShoppingCartPosition($shoppingCart->ID, $this->ID);
                if ($status['error']) {
                    if ($position instanceof ShoppingCartPosition
                     && $position->implicatePosition
                    ) {
                        $position->setImplicationStatus(false);
                        $voucherHistory = VoucherHistory::create();
                        $voucherHistory->add($this, $member, 'removed');
                    }
                } else {
                    if ($position instanceof ShoppingCartPosition
                     && (bool) $position->implicatePosition === false
                    ) {
                        $voucherHistory = VoucherHistory::create();
                        $voucherHistory->add($this, $member, 'redeemed');
                        $position->setImplicationStatus(true);
                    }
                }
            }
        }
    }

    /**
     * Performs checks related to the shopping cart entries to ensure that
     * the voucher is allowed to be placed in the cart.
     * 
     * Returns:
     * <code>
     * [
     *     'error'    => bool,
     *     'messages' => string[],
     * ]
     * </code>
     *
     * @param ShoppingCart $shoppingCart the shopping cart to check against
     *
     * @return array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>,
     *         Sebastian Diel <sdiel@pixeltricks.de>
     * @since 14.05.2020
     */
    public function areShoppingCartConditionsMet(ShoppingCart $shoppingCart) : array
    {
        $error    = false;
        $messages = [];
        $message  = null;
        if (!$this->isShoppingCartAmountValid($shoppingCart->getTaxableAmountWithoutFeesAndCharges([self::class]))) {
            $error      = true;
            $messages[] = $this->fieldLabel('ErrorValueNotValid');
        } elseif (!$this->isValidForShoppingCartItems($shoppingCart->ShoppingCartPositions(), $message)) {
            $error      = true;
            $messages[] = $message === null ? $this->fieldLabel('ErrorItemsNotValid') : $message;
        }
        return [
            'error'    => $error,
            'messages' => $messages
        ];
    }

    /**
     * Checks if the given code is valid by comparing it to the code in the
     * database.
     *
     * @param string $code the voucher code
     *
     * @return bool
     */
    public function isCodeValid(string $code) : bool
    {
        return $this->code === $code;
    }

    /**
     * Checks if the given voucher code is already in the shopping cart.
     *
     * @param ShoppingCart $shoppingCart the shopping cart object
     *
     * @return bool
     */
    public function isInShoppingCartAlready(ShoppingCart $shoppingCart) : bool
    {
        return ShoppingCartPosition::combinationExists($shoppingCart->ID, $this->ID);
    }

    /**
     * Checks if the customer is eligible to redeem the voucher by making sure
     * that he/she is not excluded by the RestrictTo-relations.
     *
     * @param Member $member the customer object
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>,
     *         Sebastian Diel <sdiel@pixeltricks.de>
     * @since 14.05.2020
     */
    public function isCustomerEligible(Member $member) : bool
    {
        $isEligibleByUndefinedMembership      = false;
        $isEligibleByMembership               = false;
        $isEligibleByUndefinedGroupMembership = false;
        $isEligibleByGroupMembership          = false;
        // check if voucher is restricted to single members and if so, if
        // customer is one of those members.
        if ($this->RestrictToMember()->count() > 0) {
            $isEligibleByMembership = $this->RestrictToMember()->byID($member->ID) instanceof Member;
        } else {
            // no restriction on membership level
            $isEligibleByUndefinedMembership = true;
        }
        // check if voucher is restricted to groups and if so, if customer is
        // in allowed groups
        if ($this->RestrictToGroup()->count() > 0) {
            $isEligibleByGroupMembership = $this->findDataObjectSetInSetByKey($this->RestrictToGroup(), $member->Groups(), 'ID');
        } else {
            // no restriction on group membership level
            $isEligibleByUndefinedGroupMembership = true;
        }
        // --------------------------------------------------------------------
        // check if user has a permission for this voucher
        // --------------------------------------------------------------------
        if ($isEligibleByMembership
         && $isEligibleByGroupMembership
        ) {
            return true;
        }
        // exceptional case: no membership levels configured
        if ($isEligibleByUndefinedMembership
         && $isEligibleByUndefinedGroupMembership
        ) {
            return true;
        }
        // exceptional case: user is not in allowed groups, but has a
        // permission on membership level
        if (!$isEligibleByGroupMembership
         && $isEligibleByMembership
        ) {
            return true;
        }
        // exceptional case: user is not allowed by membership, but has a
        // permission on group membership level
        if (!$isEligibleByMembership
         && $isEligibleByGroupMembership
        ) {
            return true;
        }
        return false;
    }
    
    /**
     * can be used to return if a voucher is already fully redeemd,
     * set error message in checkifAllowedInShoppingCart()
     * 
     * @param Member $member    the member object
     * @param int    $voucherID id of the voucher
     * 
     * @return bool
     */
    protected function isCompletelyRedeemedAlready(Member $member, int $voucherID) : bool
    {
        // Implement in descendants if needed
        return false;
    }

    /**
     * Checks if the shoppingcart total amount is within the boundaries of
     * this voucher if they are defined.
     *
     * @param SilverStripeDBMoney $amount the amount of the shoppingcart.
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>,
     *         Sebastian Diel <sdiel@pixeltricks.de>
     * @since 14.05.2020
     */
    public function isShoppingCartAmountValid(SilverStripeDBMoney $amount) : bool
    {
        $cacheKey = (string) $amount->getAmount();
        if (!array_key_exists($cacheKey, $this->isShoppingCartAmountValid)) {
            $isMinimumValid          = false;
            $isUndefinedMinimumValid = false;
            $isMaximumValid          = false;
            $isUndefinedMaximumValid = false;
            if ($this->minimumShoppingCartValue->getAmount() > 0) {
                $isMinimumValid = $amount->getAmount() >= $this->minimumShoppingCartValue->getAmount();
            } else {
                $isUndefinedMinimumValid = true;
            }
            if ($this->maximumShoppingCartValue->getAmount() > 0) {
                $isMaximumValid = $amount->getAmount() <= $this->maximumShoppingCartValue->getAmount();
            } else {
                $isUndefinedMaximumValid = true;
            }
            $this->isShoppingCartAmountValid[$cacheKey] = ($isMinimumValid
                                                        && $isMaximumValid)
                                                       || ($isUndefinedMinimumValid
                                                        && $isUndefinedMaximumValid)
                                                       || ($isUndefinedMinimumValid
                                                        && $isMaximumValid)
                                                       || ($isUndefinedMaximumValid
                                                        && $isMinimumValid);
        }
        return $this->isShoppingCartAmountValid[$cacheKey];
    }

    /**
     * Checks if there are restrictions for this voucher in regars to the
     * items in the shopping cart.
     *
     * @param SS_List $shoppingCartPositions the shoppingcartposition object
     * @param string  &$message              Alternative message to display in cart
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>,
     *         Sebastian Diel <sdiel@pixeltricks.de>
     * @since 14.05.2020
     */
    public function isValidForShoppingCartItems(SS_List $shoppingCartPositions, string &$message = null) : bool
    {
        $cacheKey = (string) implode('_', $shoppingCartPositions->map('ID', 'ID')->toArray());
        if (!array_key_exists($cacheKey, $this->isValidForShoppingCartItems)) {
            $isValidByUndefinedProduct      = false;
            $isValidByProduct               = false;
            $isValidByUndefinedProductGroup = false;
            $isValidByProductGroup          = false;
            if ($this->RestrictToProducts()->exists()) {
                foreach ($this->RestrictToProducts() as $restrictedProduct) {
                    foreach ($shoppingCartPositions as $shoppingCartPosition) {
                        if ($shoppingCartPosition->Product()->ID == $restrictedProduct->ID) {
                            $isValidByProduct = true;
                            break(2);
                        }
                    }
                }
            } else {
                $isValidByUndefinedProduct = true;
            }
            if ($this->RestrictToProductGroups()->exists()) {
                foreach ($this->RestrictToProductGroups() as $restrictedProductGroup) {
                    foreach ($shoppingCartPositions as $shoppingCartPosition) {
                        if ($shoppingCartPosition->Product()->ProductGroup()->ID == $restrictedProductGroup->ID) {
                            $isValidByProductGroup = true;
                            break(2);
                        }
                    }
                }
            } else {
                $isValidByUndefinedProductGroup = true;
            }
            $isValid = ($isValidByProduct
                     && $isValidByProductGroup)
                    || ($isValidByUndefinedProduct
                     && $isValidByUndefinedProductGroup)
                    || (!$isValidByProductGroup
                     && $isValidByProduct)
                    || (!$isValidByProduct
                     && $isValidByProductGroup);
            $this->extend('updateIsValidForShoppingCartItems', $isValid, $shoppingCartPositions, $message);
            $this->isValidForShoppingCartItems[$cacheKey] = $isValid;
        }
        return $this->isValidForShoppingCartItems[$cacheKey];
    }

    /**
     * Returns the number of remaining vouchers.
     *
     * @return int
     */
    public function getRemainingVouchers() : int
    {
        $remainingVouchers = 0;
        if ($this->quantity !== null) {
            $remainingVouchers = $this->quantity - $this->quantityRedeemed;
        }
        return $remainingVouchers;
    }

    /**
     * Checks if the voucher is active and if there are enough remaining
     * vouchers if the quantity is restricted.
     * 
     * @param Member $member Member context
     *
     * @return bool
     */
    public function isRedeemable(Member $member = null) : bool
    {
        $isRedeemable = false;
        if ($this->isActive) {
            if ($this->quantity == -1
             || $this->getRemainingVouchers() > 0
            ) {
                $isRedeemable = true;
            }
        }
        return $isRedeemable;
    }

    /**
     * Redeem the voucher.
     *
     * @param Member $member the customer object
     * @param string $action the action for commenting
     *
     * @return void
     */
    public function redeem(Member $member, string $action = 'redeemed') : void
    {
        $voucherHistory = VoucherHistory::create();
        $voucherHistory->add($this, $member, $action);
        ShoppingCartPosition::add($member->ShoppingCart()->ID, $this->ID);
    }

    /**
     * Remove the voucher from the shopping cart.
     *
     * @param Member $member the customer object
     * @param string $action the action for commenting
     *
     * @return void
     *
     */
    public function removeFromShoppingCart(Member $member, string $action = 'removed') : void
    {
        $this->extend('onBeforeRemoveFromShoppingCart', $member);
        $voucherHistory = VoucherHistory::create();
        $voucherHistory->add($this, $member, $action);
        ShoppingCartPosition::remove($member->ShoppingCart()->ID, $this->ID);
        $this->extend('onAfterRemoveFromShoppingCart', $member);
    }

    /**
     * Returns an instance of a silvercart voucher object for the given
     * shopping cart.
     *
     * @param ShoppingCart $shoppingCart The shopping cart object
     *
     * @return Voucher
     * 
     * @deprecated
     */
    public function loadObjectForShoppingCart(ShoppingCart $shoppingCart) : Voucher
    {
        $voucherHistory = $this->getLastHistoryEntry($shoppingCart);
        if ($voucherHistory) {
            $voucher = self::get()->byID($voucherHistory->VoucherObjectID);
            if ($voucher instanceof Voucher) {
                return $voucher;
            }
        }
        return $this;
    }

    /**
     * Returns all vouchers related to the shopping cart.
     *
     * @param ShoppingCart $shoppingCart The shopping cart object
     *
     * @return DataList
     */
    public function loadObjectsForShoppingCart(ShoppingCart $shoppingCart) : DataList
    {
        $ids = array_merge([-1], $shoppingCart->VoucherPositions()->map('ID', 'VoucherID')->toArray());
        return self::get()->filter('ID', $ids);
    }

    /**
     * This method is a hook that gets called by the shoppingcart.
     *
     * It returns an entry for the cart listing.
     *
     * @param ShoppingCart $shoppingCart       The shoppingcart object
     * @param Member       $member                       The customer object
     * @param bool         $taxable                      Indicates if taxable or nontaxable entries should be returned
     * @param array        $excludeShoppingCartPositions Positions that shall not be counted; can contain the ID or the className of the position
     * @param bool         $createForms                  Indicates wether the form objects should be created or not
     *
     * @return ArrayList
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>,
     *         Sascha Koehler <skoehler@pixeltricks.de>
     * @since 14.12.2016
     */
    public function ShoppingCartPositions(ShoppingCart $shoppingCart, Member $member = null, bool $taxable = true, array $excludeShoppingCartPositions = [], bool $createForms = true) : ArrayList
    {
        $positions   = ArrayList::create();
        $vhTableName = VoucherHistory::config()->table_name;
        $addedIDs    = [];
        $records     = DB::query(
            "SELECT DISTINCT VHis.VoucherObjectID, VHis.LastEdited"
          . " FROM {$vhTableName} VHis"
          . " WHERE VHis.ShoppingCartID = {$shoppingCart->ID}"
          . " ORDER BY VHis.LastEdited DESC"
        );
        foreach ($records as $record) {
            if (in_array($record['VoucherObjectID'], $addedIDs)) {
                continue;
            }
            $addedIDs[] = $record['VoucherObjectID'];
            $voucher    = Voucher::get()->byID($record['VoucherObjectID']);
            if ($voucher instanceof Voucher) {
                $position = ShoppingCartPosition::getVoucherShoppingCartPosition($shoppingCart->ID, $voucher->ID);
                if ($position instanceof ShoppingCartPosition
                 && $position->implicatePosition
                ) {
                    $shoppingCartPositions = $voucher->getShoppingCartPositions($shoppingCart, $taxable, $excludeShoppingCartPositions, $createForms);
                    if ($shoppingCartPositions->exists()) {
                        foreach ($shoppingCartPositions as $shoppingCartPosition) {
                            $positions->push($shoppingCartPosition);
                        }
                    }
                }
            }
        }
        return $positions;
    }

    /**
     * This method is a hook that gets called by the shoppingcart.
     *
     * It disconnects the voucher from the shopping cart after they are converted to order positions
     *
     * @param ShoppingCart $shoppingCart The shoppingcart object
     * @param Member       $member       The customer object
     * @param Order        $order        The order object
     *
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     *         Sascha Koehler <skoehler@pixeltricks.de>
     * @since 28.11.2016
     */
    public function ShoppingCartConvert(ShoppingCart $shoppingCart, Member $member, Order $order) : void
    {
        $shoppingCartPositions = ShoppingCartPosition::get()->filter('ShoppingCartID', $shoppingCart->ID);
        if ($shoppingCartPositions->exists()) {
            $this->extend('onBeforeShoppingCartConvert', $shoppingCart, $member, $order);
            foreach ($shoppingCartPositions as $shoppingCartPosition) {
                if (!$shoppingCartPosition->Voucher()->exists()) {
                    $shoppingCartPosition->delete();
                    continue;
                }
                $originalVoucher = self::get()->byID($shoppingCartPosition->VoucherID);
                if (!($originalVoucher instanceof Voucher)) {
                    $shoppingCartPosition->delete();
                    continue;
                }
                if ($shoppingCartPosition->Voucher()->areShoppingCartConditionsMet($shoppingCart)['error'] === true) {
                    continue;
                }
                // Adjust quantity
                if ($originalVoucher->quantity > 0) {
                    $originalVoucher->quantity -= 1;
                }
                if (!$member->Vouchers()->find('ID', $originalVoucher->ID)) {
                    // increase redeemd quantity only if no relation exists
                    $originalVoucher->quantityRedeemed += 1;
                }
                // Call conversion method on every voucher
                if (method_exists($shoppingCartPosition->Voucher(), 'convert')) {
                    $shoppingCartPosition->Voucher()->convert($shoppingCart, $shoppingCartPosition, $originalVoucher, $member);
                }
                // save changes to original voucher
                $originalVoucher->write();
                // And remove from the customers shopping cart
                $shoppingCartPosition->delete();
            }
            $this->extend('onAfterShoppingCartConvert', $shoppingCart, $member, $order);
        }
    }

    /**
     * This method is a hook that gets called by the shoppingcart.
     *
     * It returns taxable entries for the cart listing.
     *
     * @param ShoppingCart $shoppingCart The SilverCart shopping cart object
     * @param Member       $member       The member object
     *
     * @return ArrayList
     */
    public function TaxableShoppingCartPositions(ShoppingCart $shoppingCart, Member $member) : ArrayList
    {
        return $this->ShoppingCartPositions($shoppingCart, $member, true);
    }

    /**
     * This method is a hook that gets called by the shoppingcart.
     *
     * It returns nontaxable entries for the cart listing.
     *
     * @param ShoppingCart $shoppingCart The SilverCart shopping cart object
     * @param Member       $member       The member object
     *
     * @return ArrayList
     */
    public function NonTaxableShoppingCartPositions(ShoppingCart $shoppingCart, Member $member) : ArrayList
    {
        return $this->ShoppingCartPositions($shoppingCart, $member, false);
    }

    /**
     * Return the last history entry or NULL if none was found for the
     * given shoppingcart object.
     *
     * @param ShoppingCart $shoppingCart the shoppingcart object
     *
     * @return VoucherHistory|NULL
     */
    public function getLastHistoryEntry(ShoppingCart $shoppingCart) : ?VoucherHistory
    {
        return VoucherHistory::get()
                ->filter('ShoppingCartID', $shoppingCart->ID)
                ->sort('Created', 'DESC')
                ->first();
    }

    /**
     * This method is a hook that gets called by the shoppingcart.
     *
     * It returns input fields for the entry of the voucher code and insertion
     * into the shopping cart.
     *
     * @param ShoppingCart $shoppingCart the shoppingcart object
     *
     * @return ArrayList
     */
    public function ShoppingCartActions(ShoppingCart $shoppingCart) : ArrayList
    {
        $actions    = ArrayList::create();
        $controller = Controller::curr();
        // Don't initialise when called from within the cms
        if (!($controller instanceof \PageController)
         || !$controller->hasMethod('AddVoucherCodeForm')
         || !Voucher::get()->filter('isActive', true)->exists()
        ) {
            return $actions;
        }
        $actions->push(ArrayData::create([
            'moduleOutput' => $controller->AddVoucherCodeForm(),
        ]));
        return $actions;
    }

    /**
     * Returns the amount to consider in the shopping cart total calculation.
     *
     * @return DBMoney
     */
    public function ShoppingCartTotal() : ?DBMoney
    {
        $member = Customer::currentUser();
        if (!($member instanceof Member)
         || $member->ShoppingCartID === 0
        ) {
            return NULL;
        }
        $amount      = 0;
        $vhTableName = VoucherHistory::config()->table_name;
        $addedIDs    = [];
        $records     = DB::query(
            "SELECT DISTINCT VHis.VoucherObjectID, VHis.LastEdited"
          . " FROM {$vhTableName} VHis"
          . " WHERE VHis.ShoppingCartID = {$member->ShoppingCartID}"
          . " ORDER BY VHis.LastEdited DESC"
        );
        foreach ($records as $record) {
            if (in_array($record['VoucherObjectID'], $addedIDs)) {
                continue;
            }
            $addedIDs[] = $record['VoucherObjectID'];
            $voucher    = self::get()->byID($record['VoucherObjectID']);
            if ($voucher instanceof Voucher) {
                $amount += $voucher->getShoppingCartTotal()->getAmount();
            }
        }
        return DBMoney::create()->setAmount($amount);
    }

    /**
     * Returns the amount to consider in the shopping cart total calculation.
     *
     * @return DBMoney
     */
    public function getShoppingCartTotal() : DBMoney
    {
        return DBMoney::create();
    }

    /**
     * Define the backend administration masks.
     *
     * @return FieldList
     */
    public function  getCMSFields() : FieldList
    {
        $this->beforeUpdateCMSFields(function(FieldList $fields) {
            $fields->removeByName('Members');
            if (empty($this->code)) {
                $code = self::generateCode();
                $fields->dataFieldByName('code')->setAttribute('placeholder', $code);
                $fields->addFieldToTab('Root.Main', HiddenField::create('GeneratedCode', '', $code));
            }
            $historyField = $fields->dataFieldByName('VoucherHistory');
            if ($historyField instanceof GridField) {
                $historyField->getConfig()->removeComponentsByType(GridFieldAddNewButton::class);
                $historyField->getConfig()->removeComponentsByType(GridFieldAddExistingAutocompleter::class);
                $historyField->getConfig()->removeComponentsByType(GridFieldFilterHeader::class);
            }
        });
        return DataObjectExtension::getCMSFields($this);
    }
    
    /**
     * Returns the custom VoucherValidator to use for CMS field validation.
     * 
     * @return VoucherValidator
     */
    public function getCMSValidator() : VoucherValidator
    {
        $validator = VoucherValidator::create();
        $validator->setForVoucher($this);
        $this->extend('updateCMSValidator', $validator);
        return $validator;
    }

    /**
     * Allows user code to hook into DataObject::getCMSValidator prior to 
     * updateCMSValidator being called on extensions.
     *
     * @param callable $callback The callback to execute
     */
    protected function beforeUpdateCMSValidator(callable $callback) : void
    {
        $this->beforeExtending('updateCMSValidator', $callback);
    }
    
    /**
     * On before write.
     * 
     * @return void
     */
    protected function onBeforeWrite() : void
    {
        parent::onBeforeWrite();
        if ($this->canEdit()) {
            if (empty($this->code)
             && array_key_exists('GeneratedCode', $_POST)
             && !empty($_POST['GeneratedCode'])
            ) {
                $this->code = $_POST['GeneratedCode'];
            }
        }
    }

    /**
     * Checks if a tax rate is attributed to this voucher. If not, we try
     * to get a 0% rate.
     *
     * @return void
     */
    public function onAfterWrite() : void
    {
        parent::onAfterWrite();
        if (!$this->Tax()->exists()) {
            $taxRateZero = Tax::get()
                    ->filter('Rate', 0)
                    ->first();
            if ($taxRateZero instanceof Tax) {
                $this->TaxID = $taxRateZero->ID;
                $this->write();
            }
        }
    }

    /**
     * Returns a SS_List for the display of the voucher positions in the
     * shoppingcart.
     *
     * @param ShoppingCart $shoppingCart the shoppingcart object
     *
     * @return SS_List
     */
    public function getShoppingCartPositions(ShoppingCart $shoppingCart, bool $taxable = true, array $excludeShoppingCartPositions = [], bool $createForms = true) : ArrayList
    {
        // Implement in descendants
        return ArrayList::create();
    }

    /**
     * Check if a value of a key of a SS_List is contained in another
     * SS_List.
     *
     * @param SS_List $set1 the first set to search in
     * @param SS_List $set2 the second set to search in
     * @param string  $key  the key to search for
     *
     * @return bool
     */
    private function findDataObjectSetInSetByKey(SS_List $set1, SS_List $set2, string $key) : bool
    {
        $foundKey = false;
        foreach ($set2 as $iteratorSet) {
            if ($set1->find($key, $iteratorSet->$key)) {
                $foundKey = true;
                break;
            }
        }
        return $foundKey;
    }
}