<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\SkuGenerator;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use PHPUnit\Framework\TestCase;

final class SkuGeneratorTest extends TestCase
{
    private FakerGenerator $faker;
    private SkuGenerator $g;

    protected function setUp(): void
    {
        $this->faker = FakerFactory::create();
        $this->g     = new SkuGenerator();
    }

    private static function prefix(string $name): string
    {
        return mb_strtoupper(mb_substr(trim($name), 0, 4));
    }

    public function testGeneratesExpectedFormat(): void
    {
        $sku = $this->g->generate('Macbook Pro');
        $this->assertMatchesRegularExpression('/^PROD-MACB-[0-9a-f]{7}$/i', $sku);
    }

    public function testRandomNamesRespectFormat(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $name = $this->faker->words(mt_rand(1, 3), true);
            $sku  = $this->g->generate($name);

            $expectedPrefix = self::prefix($name);
            $this->assertMatchesRegularExpression(
                '/^PROD-' . preg_quote($expectedPrefix, '/') . '-[0-9a-f]{7}$/i',
                $sku
            );
        }
    }

    public function testShortNamesAreHandled(): void
    {
        $len  = mt_rand(1, 3);
        $name = substr($this->faker->lexify(str_repeat('?', $len)), 0, $len);

        $sku = $this->g->generate($name);
        $expectedPrefix = self::prefix($name);

        $this->assertStringStartsWith('PROD-' . $expectedPrefix . '-', $sku);
        $this->assertMatchesRegularExpression(
            '/^PROD-' . preg_quote($expectedPrefix, '/') . '-[0-9a-f]{7}$/i',
            $sku
        );
    }

    public function testTrimsWhitespace(): void
    {
        $raw  = '  ' . $this->faker->word() . '   ';
        $sku  = $this->g->generate($raw);
        $expectedPrefix = self::prefix($raw);

        $this->assertMatchesRegularExpression(
            '/^PROD-' . preg_quote($expectedPrefix, '/') . '-[0-9a-f]{7}$/i',
            $sku
        );
    }

    public function testRandomPartChanges(): void
    {
        $a = $this->g->generate('Widget');
        $b = $this->g->generate('Widget');
        $this->assertNotSame($a, $b);
    }

    public function testRandomPartIsHexOfLength7(): void
    {
        $sku = $this->g->generate('Gadget');
        $this->assertMatchesRegularExpression('/^PROD-[^-]+-([0-9a-f]{7})$/i', $sku);

        preg_match('/^PROD-[^-]+-([0-9a-f]{7})$/i', $sku, $m);
        $this->assertCount(2, $m);
        $this->assertSame(7, strlen($m[1]));
        $this->assertTrue(ctype_xdigit($m[1]));
    }

    public function testServiceIsStateless(): void
    {
        $name1 = $this->faker->words(2, true);
        $name2 = $this->faker->words(2, true);

        $sku1a = $this->g->generate($name1);
        $sku1b = $this->g->generate($name1);
        $sku2a = $this->g->generate($name2);

        $this->assertNotSame($sku1a, $sku1b);
        $this->assertNotSame($sku1a, $sku2a);
    }
}
