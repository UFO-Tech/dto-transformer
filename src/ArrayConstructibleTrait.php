<?php

namespace Ufo\DTO;


use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;

trait ArrayConstructibleTrait
{
    /**
     * @throws BadParamException
     * @throws NotSupportDTOException
     */
    public static function fromArray(array $data, array $renameKey = [], array $context = []): static
    {
        /**
         * @var static $self
         */
        $self = DTOTransformer::fromArray(static::class, $data, $renameKey, context: $context);
        return $self;
    }
}