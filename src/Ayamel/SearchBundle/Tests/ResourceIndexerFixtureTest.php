<?php

namespace Ayamel\SearchBundle\Tests;

use Ayamel\ApiBundle\Tests\FixturedTestCase;

class ResourceIndexerFixtureTest extends FixturedTestCase
{
    // TODO: someday, it might be wise to merge this with ResourceIndexerTest, using fixtures throughout
    public function testIndexDeletedResource()
    {
        $id = $this->fixtureData['AyamelResourceBundle:Resource'][0]->getId();
        $mongoId = new \MongoId($id);
        $manager = $this->getClient()->getContainer()->get('doctrine_mongodb')->getManager();
        $manager->getConnection()->initialize();
        $mongo = $manager->getConnection()->getMongo();
        $collection = $mongo->selectCollection("ayamel_test", "resources");
        $newdata = array('$set' => array("status" => "deleted"));
        $result = $collection->update(["_id" => $mongoId], $newdata);
        $indexer = $this->getClient()->getContainer()->get('ayamel.search.resource_indexer');
        $indexer->indexResource($id);
    }
}