<?php

namespace Ufo\DTO;


use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\IDTOFromArrayTransformer;
use Ufo\DTO\Interfaces\IDTOFromSmartArrayTransformer;

abstract class BaseDTOFromArrayTransformer implements IDTOFromArrayTransformer, IDTOFromSmartArrayTransformer
{
    const string DTO_CLASSNAME = '$className';

    /**
     *  default namespace for DTO
     */
    const string DTO_NS_KEY = '$defaultNamespace';

    /**
     * Creates a DTO object from an associative array.
     *
     * @param string $classFQCN
     * @param array $data
     * @param array<string,string|null> $renameKey
     * @param array<string, string> $namespaces
     * @return object
     */
    public static function fromArray(string $classFQCN, array $data, array $renameKey = [], array $namespaces = []): object
    {
        $classes = explode('|', $classFQCN);

        $badParams = [];

        foreach ($classes as $class) {
            try {
                if (!TypeHintResolver::isRealClass($class) ) {
                    $class = TypeHintResolver::typeWithNamespaceOrDefault(
                        $class,
                        $namespaces,
                        static::DTO_NS_KEY
                    ) ?? throw new NotSupportDTOException('Invalid class FQCN: ' . $class);
                }
                return static::singleFromArray($class, $data, $renameKey, $namespaces);
            } catch (NotSupportDTOException|BadParamException $e) {
                if (count($classes) === 1) throw $e;

                $badParams[$class] = $e->getMessage();
            }
        }

        if (!empty($badParams)) {
            $details = self::formatClassErrors($badParams);
            throw new BadParamException(
                sprintf(
                    "Invalid data for DTOs (%s).%s",
                    implode(' | ', array_keys($badParams)),
                    $details
                )
            );
        }

        throw new NotSupportDTOException("Invalid class names: $classFQCN");
    }


    /**
     * @param array<string, string> $badParams
     * @return string
     */
    private static function formatClassErrors(array $badParams = []): string
    {
        $lines = [];
        foreach ($badParams as $class => $msg) {
            $msg = trim((string)$msg);
            $lines[] = sprintf(" - %s: %s", $class, $msg !== '' ? $msg : '(no message)');
        }
        return PHP_EOL . implode(PHP_EOL, $lines);
    }

    protected static function singleFromArray(string $classFQCN, array $data, array $renameKey = [], array $namespaces = []): object
    {
        if (!static::isSupportClass($classFQCN)) {
            throw new NotSupportDTOException(static::class . ' is not support transform for ' . $classFQCN);
        }
        try {
            try {
                return static::fromSmartArray($data, $renameKey, namespaces: $namespaces);
            } catch (NotSupportDTOException) {
                return static::transformFromArray($classFQCN, $data, $renameKey, namespaces: $namespaces);
            }
        } catch (\Throwable $e) {
            throw new BadParamException($e->getMessage(), $e->getCode(), $e);
        }
    }

    abstract protected static function transformFromArray(string $classFQCN, array $data, array $renameKey = [], array $namespaces = []): object;

}