<?php

namespace Oasis\SlimVue\Tests;

use Eris\Generators;
use Eris\TestTrait;
use Oasis\SlimVue\SlimVueBridgeInterface;
use Oasis\SlimVue\TwigBridgeInfo;
use PHPUnit\Framework\TestCase;

class TwigBridgeInfoTest extends TestCase
{
    use TestTrait;
    // ── constructor & interface ──

    public function testImplementsBridgeInterface(): void
    {
        $bridge = new TwigBridgeInfo();
        $this->assertInstanceOf(SlimVueBridgeInterface::class, $bridge);
    }

    public function testDefaultConstructorRendersEmptyArray(): void
    {
        $bridge = new TwigBridgeInfo();
        // PHP empty array json_encodes to '[]', not '{}'
        $this->assertSame('[]', $bridge->render());
    }

    public function testConstructorWithInitialData(): void
    {
        $bridge = new TwigBridgeInfo(['foo' => 'bar']);
        $decoded = \json_decode($bridge->render(), true);
        $this->assertSame(['foo' => 'bar'], $decoded);
    }

    // ── add() ──

    public function testAddScalarString(): void
    {
        $bridge = new TwigBridgeInfo();
        $bridge->add('name', 'alice');
        $decoded = \json_decode($bridge->render(), true);
        $this->assertSame('alice', $decoded['name']);
    }

    public function testAddScalarInt(): void
    {
        $bridge = new TwigBridgeInfo();
        $bridge->add('count', 42);
        $decoded = \json_decode($bridge->render(), true);
        $this->assertSame(42, $decoded['count']);
    }

    public function testAddScalarFloat(): void
    {
        $bridge = new TwigBridgeInfo();
        $bridge->add('pi', 3.14);
        $decoded = \json_decode($bridge->render(), true);
        $this->assertSame(3.14, $decoded['pi']);
    }

    public function testAddScalarBool(): void
    {
        $bridge = new TwigBridgeInfo();
        $bridge->add('active', true);
        $decoded = \json_decode($bridge->render(), true);
        $this->assertTrue($decoded['active']);
    }

    public function testAddNull(): void
    {
        $bridge = new TwigBridgeInfo();
        $bridge->add('empty', null);
        $decoded = \json_decode($bridge->render(), true);
        $this->assertNull($decoded['empty']);
    }

    public function testAddFlatArray(): void
    {
        $bridge = new TwigBridgeInfo();
        $bridge->add('tags', ['a', 'b', 'c']);
        $decoded = \json_decode($bridge->render(), true);
        $this->assertSame(['a', 'b', 'c'], $decoded['tags']);
    }

    public function testAddAssociativeArray(): void
    {
        $bridge = new TwigBridgeInfo();
        $bridge->add('user', ['name' => 'bob', 'age' => 30]);
        $decoded = \json_decode($bridge->render(), true);
        $this->assertSame(['name' => 'bob', 'age' => 30], $decoded['user']);
    }

    public function testAddNestedArray(): void
    {
        $bridge = new TwigBridgeInfo();
        $bridge->add('deep', ['a' => ['b' => ['c' => 1]]]);
        $decoded = \json_decode($bridge->render(), true);
        $this->assertSame(1, $decoded['deep']['a']['b']['c']);
    }

    public function testAddJsonSerializableObject(): void
    {
        $obj = new class implements \JsonSerializable {
            public function jsonSerialize(): mixed
            {
                return ['id' => 1, 'label' => 'test'];
            }
        };
        $bridge = new TwigBridgeInfo();
        $bridge->add('item', $obj);
        $decoded = \json_decode($bridge->render(), true);
        $this->assertSame(['id' => 1, 'label' => 'test'], $decoded['item']);
    }

    public function testAddArrayContainingJsonSerializableObjects(): void
    {
        $obj1 = new class implements \JsonSerializable {
            public function jsonSerialize(): mixed
            {
                return 'serialized-1';
            }
        };
        $obj2 = new class implements \JsonSerializable {
            public function jsonSerialize(): mixed
            {
                return 'serialized-2';
            }
        };
        $bridge = new TwigBridgeInfo();
        $bridge->add('items', [$obj1, $obj2]);
        $decoded = \json_decode($bridge->render(), true);
        $this->assertSame(['serialized-1', 'serialized-2'], $decoded['items']);
    }

    public function testAddNestedArrayWithJsonSerializable(): void
    {
        $obj = new class implements \JsonSerializable {
            public function jsonSerialize(): mixed
            {
                return 'inner';
            }
        };
        $bridge = new TwigBridgeInfo();
        $bridge->add('data', ['level1' => ['level2' => $obj]]);
        $decoded = \json_decode($bridge->render(), true);
        $this->assertSame('inner', $decoded['data']['level1']['level2']);
    }

    public function testAddOverwritesSameKey(): void
    {
        $bridge = new TwigBridgeInfo();
        $bridge->add('key', 'old');
        $bridge->add('key', 'new');
        $decoded = \json_decode($bridge->render(), true);
        $this->assertSame('new', $decoded['key']);
    }

    public function testAddMultipleKeys(): void
    {
        $bridge = new TwigBridgeInfo();
        $bridge->add('a', 1);
        $bridge->add('b', 2);
        $bridge->add('c', 3);
        $decoded = \json_decode($bridge->render(), true);
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $decoded);
    }

    public function testAddPlainObject(): void
    {
        $obj = new \stdClass();
        $obj->x = 10;
        $bridge = new TwigBridgeInfo();
        $bridge->add('obj', $obj);
        // stdClass is not JsonSerializable, so it's stored as-is
        // json_encode will serialize its public properties
        $decoded = \json_decode($bridge->render(), true);
        $this->assertSame(10, $decoded['obj']['x']);
    }

    // ── getExecTwig() ──

    public function testGetExecTwigReplacesPagePrefix(): void
    {
        $bridge = new TwigBridgeInfo();
        $result = $bridge->getExecTwig('slimvue/pages/index.twig');
        $this->assertSame('slimvue/controllers/index.twig', $result);
    }

    public function testGetExecTwigReplacesOnlyFirstOccurrence(): void
    {
        $bridge = new TwigBridgeInfo();
        $result = $bridge->getExecTwig('slimvue/pages/slimvue/pages/deep.twig');
        $this->assertSame('slimvue/controllers/slimvue/pages/deep.twig', $result);
    }

    public function testGetExecTwigNoMatchReturnsOriginal(): void
    {
        $bridge = new TwigBridgeInfo();
        $result = $bridge->getExecTwig('other/path/index.twig');
        $this->assertSame('other/path/index.twig', $result);
    }

    public function testGetExecTwigWithSubdirectory(): void
    {
        $bridge = new TwigBridgeInfo();
        $result = $bridge->getExecTwig('slimvue/pages/admin/dashboard.twig');
        $this->assertSame('slimvue/controllers/admin/dashboard.twig', $result);
    }

    public function testGetExecTwigEmptyString(): void
    {
        $bridge = new TwigBridgeInfo();
        $result = $bridge->getExecTwig('');
        $this->assertSame('', $result);
    }

    // ── render() ──

    public function testRenderReturnsValidJson(): void
    {
        $bridge = new TwigBridgeInfo(['key' => 'value']);
        $json = $bridge->render();
        $this->assertNotNull(\json_decode($json));
        $this->assertSame(JSON_ERROR_NONE, \json_last_error());
    }

    public function testRenderWithUnicodeData(): void
    {
        $bridge = new TwigBridgeInfo(['msg' => '你好世界']);
        $json = $bridge->render();
        $decoded = \json_decode($json, true);
        $this->assertSame('你好世界', $decoded['msg']);
    }

    public function testRenderThrowsOnUnencodableData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        // NAN cannot be JSON-encoded
        $bridge = new TwigBridgeInfo(['bad' => NAN]);
        $bridge->render();
    }

    public function testRenderWithEmptyStringValue(): void
    {
        $bridge = new TwigBridgeInfo(['key' => '']);
        $decoded = \json_decode($bridge->render(), true);
        $this->assertSame('', $decoded['key']);
    }

    public function testRenderPreservesNumericKeys(): void
    {
        $bridge = new TwigBridgeInfo();
        $bridge->add('list', [0 => 'a', 1 => 'b', 2 => 'c']);
        $json = $bridge->render();
        $decoded = \json_decode($json, true);
        $this->assertSame(['a', 'b', 'c'], $decoded['list']);
    }

    public function testRenderAfterMultipleAdds(): void
    {
        $bridge = new TwigBridgeInfo(['init' => true]);
        $bridge->add('x', 1);
        $bridge->add('y', 2);
        $decoded = \json_decode($bridge->render(), true);
        $this->assertSame(['init' => true, 'x' => 1, 'y' => 2], $decoded);
    }

    // ── PBT: Feature: release-4.0 ──

    /**
     * Feature: release-4.0, Property 1: render round-trip
     *
     * For any valid bridge data, json_decode(render(), true) should produce
     * a value equivalent to the original input.
     *
     * **Validates: Requirements 4.1**
     */
    public function testPbtRenderRoundTrip(): void
    {
        // Generate associative arrays with JSON-safe scalar values
        $scalarGen = Generators::oneOf(
            Generators::string(),
            Generators::int(),
            Generators::float(),
            Generators::bool(),
            Generators::constant(null),
        );

        // Generate a flat associative array of scalars
        $dataGen = Generators::associative([
            'str' => Generators::string(),
            'num' => Generators::int(),
            'flt' => Generators::float(),
            'flg' => Generators::bool(),
            'nil' => Generators::constant(null),
            'arr' => Generators::vector(3, $scalarGen),
        ]);

        $this->limitTo(50);
        $this->forAll($dataGen)->then(function (array $data): void {
            // Filter out NAN/INF floats which are not JSON-serializable
            $data = \array_map(function (mixed $v): mixed {
                if (\is_float($v) && (\is_nan($v) || \is_infinite($v))) {
                    return 0.0;
                }
                if (\is_array($v)) {
                    return \array_map(fn(mixed $item): mixed => \is_float($item) && (\is_nan($item) || \is_infinite($item)) ? 0.0 : $item, $v);
                }
                return $v;
            }, $data);

            $bridge = new TwigBridgeInfo($data);
            $decoded = \json_decode($bridge->render(), true);
            // Use assertEquals (not assertSame) because JSON round-trip may
            // convert 0.0 to int 0 — json_encode(0.0) produces "0", and
            // json_decode("0") returns int 0. This is expected JSON behavior.
            $this->assertEquals($data, $decoded);
        });
    }

    /**
     * Feature: release-4.0, Property 2: add() idempotence
     *
     * For the same key-value pair, calling add() twice should produce
     * the same render() output as calling it once.
     *
     * **Validates: Requirements 4.2**
     */
    public function testPbtAddIdempotence(): void
    {
        $keyGen = Generators::suchThat(
            fn(string $s): bool => $s !== '',
            Generators::string(),
        );
        $valueGen = Generators::oneOf(
            Generators::string(),
            Generators::int(),
            Generators::bool(),
            Generators::constant(null),
        );

        $this->limitTo(50);
        $this->forAll($keyGen, $valueGen)->then(function (string $key, mixed $value): void {
            // Single add
            $bridgeOnce = new TwigBridgeInfo();
            $bridgeOnce->add($key, $value);
            $renderOnce = $bridgeOnce->render();

            // Double add with same key-value
            $bridgeTwice = new TwigBridgeInfo();
            $bridgeTwice->add($key, $value);
            $bridgeTwice->add($key, $value);
            $renderTwice = $bridgeTwice->render();

            $this->assertSame($renderOnce, $renderTwice);
        });
    }

    /**
     * Feature: release-4.0, Property 3: getExecTwig() metamorphic length
     *
     * The output length minus input length should be 6 when the input starts
     * with 'slimvue/pages/', and 0 otherwise.
     * strlen("controllers") - strlen("pages") = 11 - 5 = 6
     *
     * **Validates: Requirements 4.3**
     */
    public function testPbtGetExecTwigMetamorphicLength(): void
    {
        $suffixGen = Generators::string();
        $pathGen = Generators::oneOf(
            // Paths with the prefix
            Generators::map(
                fn(string $suffix): string => 'slimvue/pages/' . $suffix,
                $suffixGen,
            ),
            // Paths without the prefix
            Generators::suchThat(
                fn(string $s): bool => !\str_starts_with($s, 'slimvue/pages/'),
                Generators::string(),
            ),
        );

        $this->limitTo(50);
        $this->forAll($pathGen)->then(function (string $path): void {
            $bridge = new TwigBridgeInfo();
            $result = $bridge->getExecTwig($path);
            $lengthDiff = \strlen($result) - \strlen($path);

            if (\str_starts_with($path, 'slimvue/pages/')) {
                $this->assertSame(6, $lengthDiff, "Expected length diff of 6 for prefixed path '$path', got $lengthDiff");
            } else {
                $this->assertSame(0, $lengthDiff, "Expected length diff of 0 for non-prefixed path '$path', got $lengthDiff");
            }
        });
    }
}
