<?php

namespace Ayamel\SearchBundle\Provider;

use Ayamel\SearchBundle\ResourceIndexer;
use FOS\ElasticaBundle\Provider\ProviderInterface;
use Elastica\Type;

/**
 * The ResourceProvider implements the necessary interface from FOSElasticaBundle to populate the search
 * index with Resources.  It uses the ResourceIndexer to convert Resources into Elastica Documents.
 *
 * @package AyamelSearchBundle
 * @author Evan Villemez
 **/
class ResourceProvider implements ProviderInterface
{
    private $indexer;
    private $type;
    private $documentManager;

    protected static function pluck($field, $values)
    {
        return array_map(function ($v) use ($field) {
            return $v[$field];
        }, $values);
    }

    public function __construct(ResourceIndexer $indexer, $batch = 100, $documentManager)
    {
        $this->indexer = $indexer;
        $this->type = "Ayamel\ResourceBundle\Document\Resource";
        $this->batch = $batch;
        $this->documentManager = $documentManager;
    }

    public function populate(\Closure $loggerClosure = null, array $options = array())
    {

        if ($loggerClosure) {
            $loggerClosure('Indexing resources...');
        }

        $rawMongoDb = $this->documentManager->getDocumentDatabase('Ayamel\ResourceBundle\Document\Resource')->getMongoDB();

        $cursor = $rawMongoDb->resources->find([], ['_id']);

        $ids = [];
        foreach ($cursor as $resource) {
            $ids[] = $resource['_id']->{'$id'};
        }

        if ($loggerClosure) {
            $loggerClosure(sprintf("Indexing %s resources.", count($ids)));
        }

        # 3rd arg: true to skip unindexable resources
        $failed = $this->indexer->indexResources($ids, $this->batch, true);

        if ($loggerClosure) {
            $loggerClosure("Finished indexing resources; skipped " . count($failed));
        }
    }
}
