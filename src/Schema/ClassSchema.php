<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema;

use ReflectionClass;

/**
 * Схема класу для гідрації: властивості, їхні атрибути, значення за замовчуванням і сировина типів.
 */
class ClassSchema
{
    protected const string MISSING_NAMESPACE = "\0";
    protected const string FINGERPRINT_SEPARATOR = "\1";

    /** @var list<PropertySchema> властивості для `toArray`: без `#[Ignore]` */
    readonly public array $exportedProperties;

    /** @var list<PropertySchema> властивості для `toArray` з `publicOnly` */
    readonly public array $publicExportedProperties;

    /** @var list<string> ключі, без яких гідрація класу гарантовано падає */
    readonly public array $requiredKeys;

    /**
     * @param array<string, string> $declaringNamespaces
     * @param list<PropertySchema> $properties
     * @param list<string> $namespaceKeys ключі namespaces, які читають типи властивостей
     */
    public function __construct(
        readonly public ReflectionClass $reflection,
        readonly public array $declaringNamespaces,
        readonly public array $properties,
        readonly public array $namespaceKeys = [],
    ) {
        $exported = [];
        $publicExported = [];
        $required = [];
        foreach ($properties as $property) {
            if ($property->ignored) {
                continue;
            }
            $exported[] = $property;
            if ($property->property->isPublic()) {
                $publicExported[] = $property;
            }
            if ($property->defaultKind === PropertySchema::DEFAULT_MISSING) {
                $required[] = $property->name;
            }
        }

        $this->exportedProperties = $exported;
        $this->publicExportedProperties = $publicExported;
        $this->requiredKeys = $required;
    }

    /**
     * Відбиток лише тих namespaces, від яких залежать схеми типів властивостей.
     *
     * @param array<string, string> $namespaces
     */
    public function namespacesFingerprint(array $namespaces): string
    {
        $fingerprint = '';
        foreach ($this->namespaceKeys as $key) {
            $fingerprint .= ($namespaces[$key] ?? static::MISSING_NAMESPACE) . static::FINGERPRINT_SEPARATOR;
        }

        return $fingerprint;
    }
}
