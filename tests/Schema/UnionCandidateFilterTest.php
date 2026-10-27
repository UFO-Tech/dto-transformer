<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Schema;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Factory\DefaultDTOTransformerFromArrayFactory;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Schema\DefaultValueResolver;
use Ufo\DTO\Schema\RequiredKeysCandidateFilter;
use Ufo\DTO\Schema\Type\TypeHintResolver;
use Ufo\DTO\Tests\Fixtures\DTO\DateTimeDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DefaultValuesDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\IgnoredRequiredDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UserDto;
use Ufo\DTO\Tests\Fixtures\DTO\UserOrDummyDTO;
use Ufo\DTO\Tests\Support\RecordingDTOFromArrayTransformer;
use Ufo\DTO\Tests\Support\TransformerFactory;
use Ufo\DTO\Transformer\DTOFromArrayTransformer;
use Ufo\DTO\Transformer\DTOFromArrayTransformerInterface;
use Ufo\DTO\Transformer\DTOToArrayTransformer;
use Ufo\DTO\Transformer\Attribute\AssertionsValidator;
use Ufo\DTO\Transformer\Attribute\AttrDTOResolver;
use Ufo\DTO\Transformer\Hydrator\CompiledTypeSchemaProviderInterface;
use Ufo\DTO\Transformer\Hydrator\DTOHydrator;
use Ufo\DTO\Transformer\Hydrator\HydratorChain;
use Ufo\DTO\Transformer\SmartClassResolver;

final class UnionCandidateFilterTest extends TestCase
{
    private const array DUMMY = ['id' => 1, 'name' => 'n'];

    /**
     * @var list<class-string<DTOTransformer>>
     */
    private array $booted = [];

    protected function tearDown(): void
    {
        foreach ($this->booted as $class) {
            $class::reset();
        }
    }

    public function testRejectsDataOnlyWhenRequiredKeyIsMissing(): void
    {
        $transformer = $this->recordingTransformer();

        $this->assertTrue($transformer->rejectsData(DummyDTO::class, ['name' => 'n']));
        $this->assertFalse($transformer->rejectsData(DummyDTO::class, self::DUMMY));
        $this->assertFalse($transformer->rejectsData(DummyDTO::class, ['id' => null, 'name' => null]));
        $this->assertFalse($transformer->rejectsData(DefaultValuesDTO::class, []));
        $this->assertFalse($transformer->rejectsData(IgnoredRequiredDTO::class, ['id' => 1]));
    }

    public function testRejectsDataDoesNotGuessWhenClassIsChosenOtherwise(): void
    {
        $transformer = $this->recordingTransformer();

        $this->assertFalse($transformer->rejectsData(DummyDTO::class, ['title' => 'n', 'id' => 1], ['name' => 'title']));
        $this->assertFalse($transformer->rejectsData(DummyDTO::class, ['$className' => UserDto::class]));
        $this->assertFalse($transformer->rejectsData('Ufo\\DTO\\Tests\\Missing', []));
        $this->assertSame([], $transformer->hydrated);
    }

    public function testUnionClassesSkipHydrationOfRejectedCandidate(): void
    {
        $recording = $this->recordingTransformer();
        $facade = $this->bootFacade($recording);

        $result = $facade::fromArray(UserDto::class . '|' . DummyDTO::class, self::DUMMY);

        $this->assertInstanceOf(DummyDTO::class, $result);
        $this->assertSame([DummyDTO::class], $recording->hydrated);
    }

    public function testUnionClassesKeepErrorMessageWhenEveryCandidateFails(): void
    {
        $classes = UserDto::class . '|' . DateTimeDTO::class . '|' . DummyDTO::class;
        $data = ['x' => 1];

        $recording = $this->recordingTransformer();
        $filtered = $this->bootFacade($recording);
        $unfiltered = $this->bootFacade($this->recordingTransformer(), filter: false);

        $this->assertSame(
            $this->failureMessage(static fn () => $unfiltered::fromArray($classes, $data)),
            $this->failureMessage(static fn () => $filtered::fromArray($classes, $data)),
        );
        $this->assertSame([UserDto::class, DateTimeDTO::class, DummyDTO::class], $recording->hydrated);
    }

    public function testUnionClassesWithRenameKeyAreNotFiltered(): void
    {
        $recording = $this->recordingTransformer();
        $facade = $this->bootFacade($recording);

        $result = $facade::fromArray(DateTimeDTO::class . '|' . DummyDTO::class, ['title' => 'n', 'id' => 1], ['name' => 'title']);

        $this->assertInstanceOf(DummyDTO::class, $result);
        $this->assertSame([DateTimeDTO::class, DummyDTO::class], $recording->hydrated);
    }

    public function testUnionFieldSkipsHydrationOfRejectedDtoBranch(): void
    {
        $recording = $this->recordingTransformer();

        $result = $recording->transformFromArray(UserOrDummyDTO::class, ['item' => self::DUMMY]);

        $this->assertInstanceOf(DummyDTO::class, $result->item);
        $this->assertSame([UserOrDummyDTO::class, DummyDTO::class], $recording->hydrated);
    }

    public function testDTOHydratorRejectsOnlyGuaranteedFailures(): void
    {
        $hydrator = new DTOHydrator($this->recordingTransformer());
        $schema = [TypeHintResolver::CLASS_FQCN => DummyDTO::class];

        $this->assertTrue($hydrator->rejects($schema, 'scalar'));
        $this->assertTrue($hydrator->rejects($schema, ['name' => 'n']));
        $this->assertFalse($hydrator->rejects($schema, self::DUMMY));
        $this->assertFalse($hydrator->rejects($schema, new DummyDTO(1, 'n')));
    }

    public function testDTOHydratorDoesNotRejectWhenCustomTransformerSupportsClass(): void
    {
        $custom = new class implements DTOFromArrayTransformerInterface {
            public function transformFromArray(
                string $classFQCN,
                array $data,
                array $renameKey = [],
                array $namespaces = [],
                array $context = [],
            ): object {
                return new DummyDTO(0, 'custom');
            }

            public function support(string $classFQCN): bool
            {
                return true;
            }
        };

        $hydrator = new DTOHydrator($this->recordingTransformer(), [$custom]);

        $this->assertFalse($hydrator->rejects([TypeHintResolver::CLASS_FQCN => DummyDTO::class], []));
    }

    private function recordingTransformer(): RecordingDTOFromArrayTransformer
    {
        $factory = new class (...TransformerFactory::fromArrayFactoryArguments()) extends DefaultDTOTransformerFromArrayFactory {
            protected function createTransformer(
                HydratorChain $chain,
                CompiledTypeSchemaProviderInterface $typeSchemaProvider,
                DefaultValueResolver $defaultValueResolver,
            ): DTOFromArrayTransformer {
                return new RecordingDTOFromArrayTransformer(
                    hydratorChain: $chain,
                    typeSchemaResolver: $this->typeSchemaResolver,
                    schemaProvider: $this->schemaProvider,
                    typeSchemaProvider: $typeSchemaProvider,
                    defaultValueResolver: $defaultValueResolver,
                    smartClassResolver: new SmartClassResolver(),
                    candidateFilter: new RequiredKeysCandidateFilter($this->schemaProvider),
                    assertionsValidator: new AssertionsValidator($this->validator),
                    attrDTOResolver: new AttrDTOResolver(),
                );
            }
        };

        $transformer = $factory->create();
        $this->assertInstanceOf(RecordingDTOFromArrayTransformer::class, $transformer);

        return $transformer;
    }

    /**
     * @return class-string<DTOTransformer>
     */
    private function bootFacade(DTOFromArrayTransformerInterface $fromArray, bool $filter = true): string
    {
        $toArray = new DTOToArrayTransformer(TransformerFactory::normalizerChain());

        $facade = $filter
            ? new class ($fromArray, $toArray) extends DTOTransformer {}
            : new class ($fromArray, $toArray) extends DTOTransformer {
                protected static function rejectsData(string $classFQCN, array $data, array $renameKey = []): bool
                {
                    return false;
                }
            };

        $facade::boot($facade);
        $this->booted[] = $facade::class;

        return $facade::class;
    }

    private function failureMessage(callable $call): string
    {
        try {
            $call();
        } catch (BadParamException $e) {
            return $e->getMessage();
        }

        $this->fail('Expected BadParamException');
    }
}
