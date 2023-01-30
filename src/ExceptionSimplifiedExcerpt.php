<?php

/**
 * germania-kg/fabricsapi-client
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Germania\FabricsApiClient;

class ExceptionSimplifiedExcerpt
{
    /**
     * Reflects the original exception/error message.
     *
     * @var string
     */
    public $message;

    /**
     * Reflects the original exception/error class.
     *
     * @var string
     */
    public $php_throwable_class;

    /**
     * Reflects the original exception/error code.
     *
     * @var int|string
     */
    public $code;

    /**
     * @param \Throwable $e Exeception or Error
     *
     * @return ExceptionSimplifiedExcerpt
     */
    public static function fromThrowable($e)
    {
        $res = new ExceptionSimplifiedExcerpt();
        $res->message = $e->getMessage();
        $res->php_throwable_class = get_class($e);
        $res->code = $e->getCode();

        return $res;
    }

    /**
     * @return \Throwable
     */
    public function restoreThrowable()
    {
        $pc = $this->php_throwable_class;

        return new $pc($this->message, $this->code);
    }
}
