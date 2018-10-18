<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;

class MongoInsertBatchTest extends TestCase
{
    public function testSerialize()
    {
        $batch = new \MongoInsertBatch($this->getCollection());
        $this->assertInternalType('string', serialize($batch));
    }

    public function testInsertBatch()
    {
        $batch = new \MongoInsertBatch($this->getCollection());

        $this->assertTrue($batch->add(['foo' => 'bar']));
        $this->assertTrue($batch->add(['bar' => 'foo']));

        $expected = [
            'nInserted' => 2,
            'ok' => true,
        ];

        $this->assertSame($expected, $batch->execute());

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(2, $newCollection->count());
        $record = $newCollection->findOne();
        $this->assertNotNull($record);
        $this->assertObjectHasAttribute('foo', $record);
        $this->assertAttributeSame('bar', 'foo', $record);
    }

    public function testInsertBatchError()
    {
        $collection = $this->getCollection();
        $batch = new \MongoInsertBatch($collection);
        $collection->createIndex(['foo' => 1], ['unique' => true]);

        $this->assertTrue($batch->add(['foo' => 'bar']));
        $this->assertTrue($batch->add(['foo' => 'bar']));

        $expected = [
            'writeErrors' => [
                [
                    'index' => 1,
                    'code' => 11000,
                ]
            ],
            'nInserted' => 1,
            'ok' => true,
        ];

        try {
            $batch->execute();
        } catch (\MongoWriteConcernException $e) {
            $this->assertSame('Failed write', $e->getMessage());
            $this->assertArraySubset($expected, $e->getDocument());
        }
    }
}
