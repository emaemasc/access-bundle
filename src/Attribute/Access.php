<?php

namespace Ema\AccessBundle\Attribute;

use Symfony\Component\ExpressionLanguage\Expression;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION)]
final class Access
{
    public function __construct(
        /**
         * Sets the first argument passed to Access().
         *
         * @var array<string|Expression>|string|Expression|null
         */
        public array|string|Expression|null $subject = null,

        /**
         * The title of access level.
         */
        public ?string $title = null,

        /**
         * The message of the exception - has a nice default if not set.
         */
        public ?string $message = null,

        /**
         * Read/write flag
         * If true, roles will be created for read and write
         */
        public ?bool $rw = false,

        /**
         * Custom options to use in actions
         */
        public ?array $options = null,

        /**
         * If set, will throw HttpKernel's HttpException with the given $statusCode.
         * If null, Security\Core's AccessDeniedException will be used.
         */
        public ?int $statusCode = null,

        /**
         * If set, will add the exception code to thrown exception.
         */
        public ?int $exceptionCode = null,
    ) {
    }
}
