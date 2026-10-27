<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests;

use PHPUnit\Framework\TestCase;
use Ufo\DTO\Tests\Fixtures\LegacyReverseNormalizer;
use Ufo\DTO\Tests\Fixtures\LegacyUpperHydrator;
use Ufo\DTO\Transformer\Context\NormalizationContext;
use Ufo\DTO\Transformer\Context\TransformationContext;
use Ufo\DTO\Transformer\Hydrator\Cache\CachedHydratorChain;
use Ufo\DTO\Transformer\Hydrator\HydratorInterface;
use Ufo\DTO\Transformer\Normalizer\NormalizerChain;
use Ufo\DTO\Transformer\Normalizer\NormalizerInterface;

final class LegacyExtensionPointsTest extends TestCase
{
    public function testHydratorWrittenAgainst3xNamesWorksInChain(): void
    {
        $hydrator = new LegacyUpperHydrator();
        $chain = new CachedHydratorChain([$hydrator]);

        $this->assertInstanceOf(HydratorInterface::class, $hydrator);
        $this->assertSame('ABC', $chain->resolve(['format' => 'upper'], 'abc', TransformationContext::fromArray()));
    }

    public function testNormalizerWrittenAgainst3xNamesWorksInChain(): void
    {
        $normalizer = new LegacyReverseNormalizer();
        $chain = new NormalizerChain([$normalizer]);

        $this->assertInstanceOf(NormalizerInterface::class, $normalizer);
        $this->assertSame('cba', $chain->normalize('abc', NormalizationContext::create()));
    }
}
