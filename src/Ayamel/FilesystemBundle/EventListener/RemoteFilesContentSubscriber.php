<?php

namespace Ayamel\FilesystemBundle\EventListener;

use Ayamel\ResourceBundle\Document\Resource;
use Ayamel\ResourceBundle\Document\FileReference;
use Ayamel\ResourceBundle\Document\ContentCollection;
use Ayamel\ResourceBundle\ResourceDocumentsFactory;
use Ayamel\ResourceApiBundle\Event\Events;
use Ayamel\ResourceApiBundle\Event\ApiEvent;
use Ayamel\ResourceApiBundle\Event\ResolveUploadedContentEvent;
use Ayamel\ResourceApiBundle\Event\HandleUploadedContentEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Handles recording content as a remote files array.  The validity of the actual file locations are not checked (currently).
 *
 * @author Evan Villemez
 */
class RemoteFilesContentSubscriber implements EventSubscriberInterface {
	
    /**
     * @var object Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;
    
    /**
     * Constructor requires the Container for retrieving the filesystem service as needed.
     *
     * @param ContainerInterface $container 
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * Array of events subscribed to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::RESOLVE_UPLOADED_CONTENT => 'onResolveContent',
            Events::HANDLE_UPLOADED_CONTENT => 'onHandleContent',
        );
    }
    
    /**
     * Check incoming request for JSON that specifies a files array with remote files.
     *
     * @param ResolveUploadedContentEvent $e 
     */
    public function onResolveContent(ResolveUploadedContentEvent $e)
    {
        $request = $e->getRequest();
        $remoteFiles = array();
        
        //if no json, or `remoteFiles` key isn't set, skip
        if (!$body = $e->getJsonBody() || !isset($body['remoteFiles'])) {
            return;
        }
        
        //create FileReference instances
        try {
            foreach ($body['remoteFiles'] as $fileData) {
            
                //TODO: need some type of validation on this, as clients can put anything into the `attributes` field
            
                $remoteFiles[] = ResourceDocumentsFactory::createFileReferenceFromArray($fileData);
            }
        } catch (\Exception $e) {
            throw new HttpException(400, $e->getMessage());
        }
        
        //if we didn't actually create anything, just return to let others try and process the event
        if(empty($remoteFiles)) {
            return;
        }
        
        $e->setContentType('remote_files');
        $e->setContentData($remoteFiles);
    }
    
    /**
     * Handle a file upload and modify resource accordingly.
     *
     * @param HandleUploadedContentEvent $e 
     */
    public function onHandleContent(HandleUploadedContentEvent $e)
    {
        if ('remote_files' !== $e->getContentType()) {
            return;
        }
        
        //make sure any old files are removed from the filesystem (if stored)
//        $this->container->get('ayamel.api.filesystem')->removeFilesForId($e->getResource()->getId());

        //set new content
        $resource->content = new ContentCollection;
        foreach ($e->getContentData() as $fileRef) {
            $resource->content->addFile($fileRef);
        }

        //set the modified resource and stop propagation of this event
        $e->setResource($resource);
    }
    
}