<?php
/**
 * PHPCompatibility, an external standard for PHP_CodeSniffer.
 *
 * @package   PHPCompatibility
 * @copyright 2012-2020 PHPCompatibility Contributors
 * @license   https://opensource.org/licenses/LGPL-3.0 LGPL3
 * @link      https://github.com/PHPCompatibility/PHPCompatibility
 */

namespace PHPCompatibility\Sniffs\ParameterValues;

use PHPCompatibility\AbstractFunctionCallParameterSniff;
use PHP_CodeSniffer\Files\File;
use PHPCSUtils\Utils\PassedParameters;

/**
 * Detect incompatible use of the third parameter for `ob_start()`.
 *
 * > The third parameter of ob_start() changed from a boolean parameter called erase
 * > (which, if set to FALSE, would prevent the output buffer from being deleted until
 * > the script finished executing) to an integer parameter called flags.
 * > Unfortunately, this results in an API compatibility break for code written prior to
 * > PHP 5.4.0 that uses the third parameter.
 *
 * PHP version 5.4
 *
 * @link https://www.php.net/manual/en/function.ob-start.php#refsect1-function.ob-start-changelog
 *
 * @since 10.0.0
 */
class ChangedObStartEraseFlagsSniff extends AbstractFunctionCallParameterSniff
{

    /**
     * Functions to check for.
     *
     * @since 10.0.0
     *
     * @var array
     */
    protected $targetFunctions = [
        'ob_start' => true,
    ];

    /**
     * Do a version check to determine if this sniff needs to run at all.
     *
     * @since 10.0.0
     *
     * @return bool
     */
    protected function bowOutEarly()
    {
        return false;
    }

    /**
     * Process the parameters of a matched function.
     *
     * @since 10.0.0
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
     * @param int                         $stackPtr     The position of the current token in the stack.
     * @param string                      $functionName The token content (function name) which was matched.
     * @param array                       $parameters   Array with information about the parameters.
     *
     * @return int|void Integer stack pointer to skip forward or void to continue
     *                  normal file processing.
     */
    public function processParameters(File $phpcsFile, $stackPtr, $functionName, $parameters)
    {
        $targetParam = PassedParameters::getParameterFromStack($parameters, 3, 'flags');
        if ($targetParam === false) {
            return;
        }

        $cleanValueLc = \strtolower($targetParam['clean']);

        $error = 'The third parameter of ob_start() changed from the boolean $erase to the integer $flags in PHP 5.4. Found: %s';
        $data  = [$targetParam['clean']];

        if ($cleanValueLc === 'true' || $cleanValueLc === 'false') {
            if ($this->supportsAbove('5.4') === true) {
                $phpcsFile->addError($error, $targetParam['start'], 'BooleanFound', $data);
            }

            return;
        }

        if ((\preg_match('`PHP_OUTPUT_HANDLER_(CLEANABLE|FLUSHABLE|REMOVABLE|STDFLAGS)`', $targetParam['clean']) === 1
            || $this->isNumber($phpcsFile, $targetParam['start'], $targetParam['end'], true) !== false)
            && $this->supportsBelow('5.3') === true
        ) {
            $phpcsFile->addError($error, $targetParam['start'], 'IntegerFound', $data);
        }
    }
}
