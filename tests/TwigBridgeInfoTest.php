<?php

namespace Oasis\SlimVue\Tests;

use Oasis\SlimVue\SlimVueBridgeInterface;
use Oasis\SlimVue\TwigBridgeInfo;
use PHPUnit\Framework\TestCase;

class TwigBridgeInfoTest extends TestCase
{
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
}
