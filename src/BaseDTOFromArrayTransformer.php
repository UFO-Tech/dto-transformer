<?php

namespace Ufo\DTO;


use Ufo\DTO\Contract\StaticDTOFromArrayTransformerInterface;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotInitializeException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Schema\Type\TypeHintResolver;

abstract class BaseDTOFromArrayTransformer implements StaticDTOFromArrayTransformerInterface
{
    const string DTO_CLASSNAME = '$className';

    /**
     *  default namespace for DTO
     * @tag
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
    public static function fromArray(string $classFQCN, array $data, array $renameKey = [], array $namespaces = [], array $context = []): object
    {
        $classes = explode('|', $classFQCN);
        $isUnion = count($classes) > 1;

        $badParams = [];
        $rejected = [];

        foreach ($classes as $class) {
            try {
                if (!TypeHintResolver::isRealClass($class) ) {
                    $class = TypeHintResolver::typeWithNamespaceOrDefault(
                        $class,
                        $namespaces,
                        static::DTO_NS_KEY
                    ) ?? throw new NotSupportDTOException('Invalid class FQCN: ' . $class);
                }
                if ($isUnion && static::isSupportClass($class) && static::rejectsData($class, $data, $renameKey)) {
                    $badParams[$class] = null;
                    $rejected[] = $class;
                    continue;
                }
                return static::singleFromArray($class, $data, $renameKey, $namespaces, $context);
            } catch (NotSupportDTOException|BadParamException $e) {
                if (!$isUnion) throw $e;

                $badParams[$class] = $e->getMessage();
            }
        }

        foreach ($rejected as $class) {
            try {
                return static::singleFromArray($class, $data, $renameKey, $namespaces, $context);
            } catch (NotSupportDTOException|BadParamException $e) {
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
    protected static function formatClassErrors(array $badParams = []): string
    {
        $lines = [];
        foreach ($badParams as $class => $msg) {
            $msg = trim((string)$msg);
            $lines[] = sprintf(" - %s: %s", $class, $msg !== '' ? $msg : '(no message)');
        }
        return PHP_EOL . implode(PHP_EOL, $lines);
    }

    protected static function singleFromArray(string $classFQCN, array $data, array $renameKey = [], array $namespaces = [], array $context = []): object
    {
        if (!static::isSupportClass($classFQCN)) {
            throw new NotSupportDTOException(static::class . ' is not support transform for ' . $classFQCN);
        }
        try {
            return static::transformFromArray($classFQCN, $data, $renameKey, namespaces: $namespaces, context: $context);
        } catch (NotInitializeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new BadParamException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * true лише тоді, коли гідрація класу з цими даними гарантовано впаде.
     */
    protected static function rejectsData(string $classFQCN, array $data, array $renameKey = []): bool
    {
        return false;
    }

    abstract public static function transformFromArray(string $classFQCN, array $data, array $renameKey = [], array $namespaces = [], array $context = []): object;

}
