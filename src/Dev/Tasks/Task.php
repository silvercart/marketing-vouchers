<?php

namespace SilverCart\Voucher\Dev\Tasks;

use SilverCart\Model\Order\OrderPosition;
use SilverCart\Voucher\Model\Voucher;
use SilverCart\Voucher\Model\Voucher\AbsoluteRebateVoucher;
use SilverCart\Voucher\Model\Voucher\RelativeRebateVoucher;
use SilverStripe\Control\CliController;

/**
 * Voucher task.
 *
 * @package Silvercart
 * @subpackage Vouchers
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 21.11.2013
 * @copyright 2013 pixeltricks GmbH
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class Task extends CliController
{
    use \SilverCart\Dev\CLITask;
    /**
     * Allowed actions.
     * 
     * @var string[]
     */
    private static $allowed_actions = [
        'update_orders',
    ];
    
    /**
     * Updates the product numbers of existing order positions without product numbers.
     * 
     * @return void
     */
    public function update_orders() : void
    {
        $affectedVouchers = Voucher::get()->excludeAny('ProductNumber', ['', null]);
        $this->printInfo("found {$affectedVouchers->count()} voucher(s) with product numbers...");
        foreach ($affectedVouchers as $voucher) {
            /* @var $voucher Voucher */
            $this->printInfo("  voucher code: {$voucher->code}");
            $this->printInfo("  product number: {$voucher->ProductNumber}");
            $affectedPositions = OrderPosition::get()
                    ->filter('VoucherCode', $voucher->code)
                    ->filterAny('ProductNumber', ['', null]);
            $this->printInfo("  found {$affectedPositions->count()} order position(s) without product numbers...");
            foreach ($affectedPositions as $position) {
                /* @var $position OrderPosition */
                $position->ProductNumber = $voucher->ProductNumber;
                $position->write();
                $this->printInfo("    added product number to position (#{$position->ID}) of order {$position->Order()->OrderNumber} (#{$position->OrderID})...");
            }
        }
        $this->printInfo("done");
    }
}