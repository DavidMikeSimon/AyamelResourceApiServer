<?php
namespace Ayamel\ResourceBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JMS\SerializerBundle\Annotation as JMS;

/**
 * API Client object, which can contain optional user-specific data
 *
 * @MongoDB\EmbeddedDocument
 * 
 */
class Client {
    
    /**
     * @MongoDB\String
     * @JMS\SerializedName("id")
     * @JMS\Type("string")
     * @JMS\ReadOnly
     */
    protected $id;
    
    /**
     * @MongoDB\String
     * @JMS\SerializedName("name")
     * @JMS\Type("string")
     * @JMS\ReadOnly
     */
    protected $name;
    
    /**
     * @MongoDB\String
     * @JMS\SerializedName("uri")
     * @JMS\Type("string")
     * @JMS\ReadOnly
     */    
    protected $uri;
    
    /**
     * @MongoDB\String
     * @JMS\SerializedName("user")
     * @JMS\Type("string")
     */
    protected $user;
    
    /**
     * @MongoDB\String
     * @JMS\SerializedName("userUri")
     * @JMS\Type("string")
     */
    protected $userUri;

    /**
     * Set id
     *
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get id
     *
     * @return string $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set uri
     *
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * Get uri
     *
     * @return string $uri
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Set user
     *
     * @param string $user
     */
    public function setUser($user)
    {
        $this->user = (string) $user;
    }

    /**
     * Get user
     *
     * @return string $user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set userUri
     *
     * @param string $userUri
     */
    public function setUserUri($userUri)
    {
        $this->userUri = $userUri;
    }

    /**
     * Get userUri
     *
     * @return string $userUri
     */
    public function getUserUri()
    {
        return $this->userUri;
    }
}
