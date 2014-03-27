<?php

namespace Ayamel\SearchBundle\Tests;

use Ayamel\SearchBundle\AsynchronousSearchTest;
use Symfony\Component\Process\Process;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;

/**
 * This test ensures that the indexer is invoked via RabbitMQ when
 * certain API events are fired.
 *
 * @package AyamelSearchBundle
 * @author Evan Villemez
 */
class AsynchronousSearchIndexerTest extends AsynchronousSearchTest
{
    public function testCreateResourceTriggersIndex()
    {
        $client = new Client('http://127.0.0.1:9200');
        $proc = $this->startRabbitListener(1);

        //create resource
        $response = $this->getJson('POST', '/api/v1/resources?_key=45678isafgd56789asfgdhf4567', array(), array(), array(
            'CONTENT_TYPE' => 'application/json'
        ), json_encode(array(
            'title' => 'Hamlet pwnz!',
            'type' => 'document'
        )));
        $this->assertSame(201, $response['response']['code']);
        $resourceId = $response['resource']['id'];
        $uploadUrl = substr($response['contentUploadUrl'], strlen('http://localhost'));

        //search document should not exist yet
        usleep(500000); //wait a bit, to give it the chance to index, even though it shouldn't
        try {
            $response = $client->get('/ayamel/resource/'.$resourceId)->send();
        } catch (ClientErrorResponseException $exception) {
        }
        $this->assertSame(404, $exception->getResponse()->getStatusCode());

        //set uploaded content as something, should force the object to index
        $content = $this->getJson('POST', $uploadUrl.'?_key=45678isafgd56789asfgdhf4567', array(), array(), array(
            'CONTENT_TYPE' => 'application/json'
        ), json_encode(array(
            'uri' => 'http://www.google.com/'
        )));
        $this->assertSame(200, $content['response']['code']);

        //the listener should index after content was uploaded
        $tester = $this;
        $proc->wait(function ($type, $buffer) use ($tester, $proc, $resourceId, $client) {
            while ($proc->isRunning()) {
                usleep(50000); //wait a tiny bit to make sure the process actually quit (... meh)
            }

            if (!$proc->isSuccessful()) {
                throw new \RuntimeException($proc->getErrorOutput());
            }

            //check that the resource was indexed
            $response = $client->get('/ayamel/resource/'.$resourceId)->send();
            $tester->assertSame(200, $response->getStatusCode());
            $data = json_decode($response->getBody(), true);
            $tester->assertSame('Hamlet pwnz!', $data['_source']['title']);
            $tester->assertFalse(isset($data['_source']['relations']));
        });

        return $resourceId;
    }

    /**
     * @depends testCreateResourceTriggersIndex
     */
    public function testModifyResourceTriggersIndex($id)
    {
        $proc = $this->startRabbitListener(1);

        $content = $this->getJson('PUT', '/api/v1/resources/'.$id.'?_key=45678isafgd56789asfgdhf4567', array(), array(), array(
            'CONTENT_TYPE' => 'application/json'
        ), json_encode(array(
            'title' => 'hamlet !pwnz'
        )));
        $this->assertSame(200, $content['response']['code']);

        $tester = $this;
        $proc->wait(function ($type, $buffer) use ($id, $tester, $proc) {
            while ($proc->isRunning()) {
                usleep(50000); //wait a tiny bit to make sure the process actually quit (... meh)
            }

            if (!$proc->isSuccessful()) {
                throw new \RuntimeException($proc->getErrorOutput());
            }

            $client = new Client('http://127.0.0.1:9200');
            $response = $client->get('/ayamel/resource/'.$id)->send();
            $tester->assertSame(200, $response->getStatusCode());
            $data = json_decode($response->getBody(), true);
            $tester->assertSame('hamlet !pwnz', $data['_source']['title']);
        });
    }

    /**
     * @depends testCreateResourceTriggersIndex
     */
    public function testCreateRelatedResourceTriggersIndex($id)
    {
        $proc = $this->startRabbitListener(2);  //this test should trigger 2 resources to index

        //create resource
        $response = $this->getJson('POST', '/api/v1/resources?_key=45678isafgd56789asfgdhf4567', array(), array(), array(
            'CONTENT_TYPE' => 'application/json'
        ), json_encode(array(
            'title' => 'Hamlet strikes back!',
            'type' => 'document'
        )));
        $this->assertSame(201, $response['response']['code']);
        $objectId = $response['resource']['id'];
        $uploadUrl = substr($response['contentUploadUrl'], strlen('http://localhost'));
        $content = $this->getJson('POST', $uploadUrl.'?_key=45678isafgd56789asfgdhf4567', array(), array(), array(
            'CONTENT_TYPE' => 'application/json'
        ), json_encode(array(
            'uri' => 'http://www.google.com/'
        )));
        $this->assertSame(200, $content['response']['code']);

        //create relation
        $relation = array(
            'subjectId' => $id,
            'objectId' => $objectId,
            'type' => 'search'
        );
        $content = $this->getJson('POST', '/api/v1/relations?_key=45678isafgd56789asfgdhf4567', array(), array(), array(
            'CONTENT_TYPE' => 'application/json'
        ), json_encode($relation));
        $this->assertSame(201, $content['response']['code']);

        //check for relations
        $tester = $this;
        $proc->wait(function ($type, $buffer) use ($id, $tester, $proc, $objectId) {
            while ($proc->isRunning()) {
                usleep(50000); //wait a tiny bit to make sure the process actually quit (... meh)
            }

            if (!$proc->isSuccessful()) {
                throw new \RuntimeException($proc->getErrorOutput());
            }
    
            $client = new Client('http://127.0.0.1:9200');

            //new resource should be in the index, no relations
            $response = $client->get('/ayamel/resource/'.$objectId)->send();
            $tester->assertSame(200, $response->getStatusCode());
            $data = json_decode($response->getBody(), true);
            $tester->assertSame('Hamlet strikes back!', $data['_source']['title']);
            $tester->assertFalse(isset($data['_source']['relations']));

            //subject should have new relations
            $response = $client->get('/ayamel/resource/'.$id)->send();
            $tester->assertSame(200, $response->getStatusCode());
            $data = json_decode($response->getBody(), true);
            $tester->assertTrue(isset($data['_source']['relations']));
            $tester->assertSame(1, count($data['_source']['relations']));
        });

        return $relation;
    }

    /**
     * @depends testCreateRelatedResourceTriggersIndex
     */
    public function testDeleteRelatedResourceTriggersIndex($relation)
    {
        $proc = $this->startRabbitListener(1);

        $content = $this->getJson('DELETE', '/api/v1/resources/'.$relation['objectId'].'?_key=45678isafgd56789asfgdhf4567', array(), array(), array(
            'CONTENT_TYPE' => 'application/json'
        ));
        $this->assertSame(200, $content['response']['code']);

        $tester = $this;
        $proc->wait(function ($type, $buffer) use ($relation, $tester, $proc) {
            while ($proc->isRunning()) {
                usleep(50000); //wait a tiny bit to make sure the process actually quit (... meh)
            }

            if (!$proc->isSuccessful()) {
                throw new \RuntimeException($proc->getErrorOutput());
            }
            
            $client = new Client('http://127.0.0.1:9200');

            //object should not be in the index
            try {
                $response = $client->get('/ayamel/resource/'.$relation['objectId'])->send();
            } catch (ClientErrorResponseException $exception) {
            }
            $this->assertSame(404, $exception->getResponse()->getStatusCode());

            //subject should be in the index, with no relations
            $response = $client->get('/ayamel/resource/'.$relation['subjectId'])->send();
            $tester->assertSame(200, $response->getStatusCode());
            $data = json_decode($response->getBody(), true);
var_dump($data['_source']['relations']);
            $tester->assertTrue(empty($data['_source']['relations']));
        });
    }

    /**
     * @depends testCreateResourceTriggersIndex
     */
    public function testDeleteResourceTriggersIndex($id)
    {
        $proc = $this->startRabbitListener(1);

        $content = $this->getJson('DELETE', '/api/v1/resources/'.$id.'?_key=45678isafgd56789asfgdhf4567', array(), array(), array(
            'CONTENT_TYPE' => 'application/json'
        ));
        $this->assertSame(200, $content['response']['code']);

        $tester = $this;
        $proc->wait(function ($type, $buffer) use ($id, $tester, $proc) {
            while ($proc->isRunning()) {
                usleep(50000); //wait a tiny bit to make sure the process actually quit (... meh)
            }

            if (!$proc->isSuccessful()) {
                throw new \RuntimeException($proc->getErrorOutput());
            }

            //object should not be in the index
            try {
                $client = new Client('http://127.0.0.1:9200');
                $response = $client->get('/ayamel/resource/'.$id)->send();
            } catch (ClientErrorResponseException $exception) {
            }
            $tester->assertSame(404, $exception->getResponse()->getStatusCode());
        });
    }

}
