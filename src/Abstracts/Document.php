<?php

/**
* @author HackerBoy.com <admin@hackerboy.com>
* @package hackerboy/json-api
*
* Document abstract class
*/

namespace HackerBoy\JsonApi\Abstracts;

use HackerBoy\JsonApi\Traits\AbstractDataConvert;
use HackerBoy\JsonApi\Query;
use Closure;
use Exception;

abstract class Document implements \JsonSerializable {

    use AbstractDataConvert;

    /**
    * JSON API version
    */
    const VERSION = '1.0';

    /**
    * Resource check result
    */
    const IS_RESOURCE = 1; // Valid and is a resource 
    const IS_COLLECTION = 2; // Valid and is a collection
    const IS_FLEXIBLE_RESOURCE = 3; // Is flexible resource
    const IS_FLEXIBLE_RESOURCE_COLLECTION = 4; // Is flexible resource
    const INVALID_RESOURCE = 5; // Invalid resource
    const INVALID_COLLECTION = 6; // Invalid collection
    const MIXED_COLLECTION = 7; // Collection contain mixed resources

    /**
    * Is this a flexible document?
    *
    * @access protected
    */
    protected $isFlexible = false;

    /**
    * Document configuration
    *
    * @access protected
    * @var array
    */
    protected $config;

    /**
    * Query object
    *
    * @access protected
    * @var \HackerBoy\JsonApi\Query
    */
    protected $query;

    /**
    * Data to encode
    *
    * @access protected
    * @var object|array
    */
    protected $data;

    /**
    * Included data to encode
    *
    * @access protected
    * @var object|array
    */
    protected $included;

    /**
    * Document errors
    *
    * @access protected
    * @var array Collection of Error element
    */
    protected $errors;

    /**
    * Document meta
    *
    * @access protected
    * @var object Meta element
    */
    protected $meta;

    /**
    * Document links
    *
    * @access protected
    * @var object Meta element
    */
    protected $links;

    /**
    * Base API URL
    *
    * @access protected
    * @var string
    */
    protected $url;

    /**
    * Model => Resource map
    *
    * @access protected
    * @var array
    */
    protected $resourceMap;

    /**
    * Document type
    *
    * @access protected
    * @var string
    */
    protected $documentType;

    /**
    * Resource instances
    *
    * @access protected
    * @var array
    */
    protected $resourceInstances = [];

    /**
    * Document constructor
    *
    * @param array Config
    */
    public function __construct($config)
    {
        if (!is_array($config) and !$this->isFlexible) {
            throw new Exception('Config must be an array');
        }

        if (!array_key_exists('resource_map', $config) and !$this->isFlexible) {
            throw new Exception('Missing resource_map in config');
        }

        $resourceMap = $config['resource_map'];

        // Check resource map
        if ((!is_array($resourceMap) or !count($resourceMap)) and !$this->isFlexible) {
            throw new Exception('Resource Map must be an array containing at least 1 element');
        } else {

            // Save resource map
            $this->resourceMap = $resourceMap;

            if ($apiUrl = @$config['api_url']) {

                $apiUrl = rtrim($apiUrl, '/');

                $this->url = $apiUrl;
            }

        }

        // Save config
        $this->config = $config;

        // Make a new query object
        $this->query = new Query();
    }

    /**
    * Get document config
    *
    * @param string Config key
    * @return mixed
    */
    final public function getConfig($key = '')
    {
        if (!$key) {
            return $this->config;
        }

        if (!$this->config) {
            return null;
        }

        return array_key_exists($key, $this->config) ? $this->config[$key] : null;
    }

    /**
    * Get API Url
    *
    * @param string|void
    * @return string
    */
    final public function getUrl($path = '')
    {
        return $this->url.'/'.$path;
    }

    /**
    * Get query object
    *
    * @param void
    * @return \HackerBoy\JsonApi\Query
    */
    public function getQuery()
    {
        return $this->query;
    }

    /**
    * Get API Url
    *
    * @param string|void
    * @return string
    */
    final public function setUrl($url)
    {
        return $this->url = rtrim($url, '/');
    }

    /**
    * Set resource to data
    *
    * @param object|array
    * @param string Data type (resource|relationship) - default is resource
    * @return object this
    */
    abstract public function setData($resource, $type = 'resource');

    /**
    * Get document data
    *
    * @return array
    */
    public function getData()
    {
        return $this->data;
    }

    /**
    * Set document type
    *
    * @param string
    * @return object this
    */
    public function setDocumentType($type)
    {
        if (in_array($type, ['relationship', 'relationships'])) {
            $type = 'relationship';
        } else {
            $type = 'resource';
        }

        $this->documentType = $type;
    }

    /**
    * Get document type
    *
    * @return string
    */
    public function getDocumentType()
    {
        return $this->documentType ?: 'resource';
    }

    /**
    * Set errors to document
    *
    * @param array|iterator|object Can be an instance or a collection of Element\Error, or simply an array of data
    * @param bool Override current value
    * @return object this
    */
    abstract public function setErrors($errors, $override = true);

    /**
    * Get document errors
    *
    * @return array
    */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
    * Set meta to document
    *
    * @param array|iterator|object
    * @param bool Override current value
    * @return object this
    */
    abstract public function setMeta($meta, $override = true);

    /**
    * Get document meta
    *
    * @return array
    */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
    * Set links to document
    *
    * @param array|iterator|object
    * @param bool Override current value
    * @return object this
    */
    abstract public function setLinks($links, $override = true);

    /**
    * Get document links
    *
    * @return array
    */
    public function getLinks()
    {
        return $this->links;
    }

    /**
    * Set objects to included
    *
    * @param object|iterator|array
    * @param bool Override current value
    * @return object this
    */
    abstract public function setIncluded($collection, $override = true);

    /**
    * Get document included data
    *
    * @return array
    */
    public function getIncluded()
    {
        // Included data only visible for document type = 'resource'
        if ($this->getDocumentType() !== 'resource') {
            return [];
        }

        return $this->included;
    }

    /**
    * Add errors to document
    *
    * @param array|iterator|object Can be an instance or a collection of Element\Error, or simply an array of data
    * @return object this
    */
    public function addErrors($errors)
    {
        return $this->setErrors($errors, false);
    }

    /**
    * Add meta to document
    *
    * @param array|iterator|object
    * @return object this
    */
    public function addMeta($meta)
    {
        return $this->setMeta($meta, false);
    }

    /**
    * Add links to document
    *
    * @param array|iterator|object
    * @return object this
    */
    public function addLinks($links)
    {
        return $this->setLinks($links, false);
    }

    /**
    * Add objects to included
    *
    * @param object|iterator|array
    * @return object this
    */
    public function addIncluded($collection)
    {
        return $this->setIncluded($collection, false);
    }

    /**
    * Check resource / collection is valid
    *
    * @param object|array Resource / Collection
    * @return constant
    */
    final public function checkResource($resource, $allowMixedCollection = false)
    {
        // Check if is flexible resource
        if ($resource instanceof \HackerBoy\JsonApi\Flexible\Resource) {

            if (!$this->isFlexible) {
                throw new Exception('Flexible resource can only be used in flexible document');
            }

            return self::IS_FLEXIBLE_RESOURCE;
        }

        // Check if resource is valid
        if (is_object($resource) and array_key_exists(get_class($resource), $this->resourceMap) and is_subclass_of($this->resourceMap[get_class($resource)], Resource::class)) {
            return self::IS_RESOURCE;
        } 

        // Check if this is a valid collection
        if (!is_iterable($resource)) {
            
            // Not? This is an invalid resource
            return self::INVALID_RESOURCE;

        }

        $firstResource = null;
        foreach ($resource as $_resource) {

            // Save first resource
            if (!$firstResource) {

                $firstResource = $_resource;
                continue;

            } else {

                // If first one is set
                if (!in_array($this->checkResource($_resource), [self::IS_FLEXIBLE_RESOURCE, self::IS_RESOURCE])) {
                    return self::INVALID_COLLECTION;
                }

            }

            if ($this->checkResource($_resource) !== self::IS_FLEXIBLE_RESOURCE) {

                // Check resource valid
                if ($this->checkResource($_resource) !== self::IS_RESOURCE) {
                        
                    // Invalid collection
                    return self::INVALID_COLLECTION;

                }

                // Check other resources the same as first one
                if (!$allowMixedCollection and (get_class($_resource) !== get_class($firstResource))) {
                    return self::MIXED_COLLECTION;
                }

            }

        }

        // All good? 
        $result = $this->checkResource($firstResource) === self::IS_FLEXIBLE_RESOURCE ? self::IS_FLEXIBLE_RESOURCE_COLLECTION : self::IS_COLLECTION;
            
        if ($result === self::IS_FLEXIBLE_RESOURCE_COLLECTION and !$this->isFlexible) {
            throw new Exception('Collection of flexible resource can only be used in flexible document');
        }

        return $result;
    }

    /**
    * Resource handler
    *
    * @param object|array Resource or collection
    * @param Closure Callback for resource
    * @param Closure Callback for collection
    * @return void
    */
    final public function resourceHandler($resource, Closure $resourceHandler, Closure $collectionHandler)
    {
        // Check resource
        $checkResource = $this->checkResource($resource, $this->isFlexible); // If document is flexible, then $allowMixedCollection is true

        switch ($checkResource) {

            case self::IS_RESOURCE :
                $resourceHandler($resource);  
            break;

            case self::IS_COLLECTION :
                $collectionHandler($resource);
            break;

            case self::IS_FLEXIBLE_RESOURCE :
                $resourceHandler($resource);

            break;

            case self::IS_FLEXIBLE_RESOURCE_COLLECTION :
                $collectionHandler($resource);
            break;

            case self::INVALID_RESOURCE :
                throw new Exception('Invalid resource');
            break;

            case self::INVALID_COLLECTION :
                throw new Exception('Invalid resource collection. Resource collection is not iterable or its containing invalid resource object.');
            break;

            case self::MIXED_COLLECTION :
                throw new Exception('Collection contains mixed resources');
            break;
            
            default:
                throw new Exception('Unknown error', 1);
            break;

        }
    }

    /**
    * Get resource handle for model object
    *
    * @param object Model object
    * @return object Resource
    */
    final public function getResource($model)
    {
        // If flexible document
        if ($this->isFlexible and !in_array($this->checkResource($model), [self::IS_FLEXIBLE_RESOURCE, self::IS_RESOURCE])) {
            $model = $this->newResourceFromArray((array) $model);
        }

        $hash = spl_object_hash($model);

        if (isset($this->resourceInstances[$hash])) {
            return $this->resourceInstances[$hash];
        }

        $checkResource = $this->checkResource($model);

        if ($checkResource === self::IS_FLEXIBLE_RESOURCE) {
            return $model;
        }

        if ($checkResource !== self::IS_RESOURCE) {
            throw new Exception('Invalid model object - cannot get resource instance');
        }

        // Make new resource instance
        $resourceInstance = new $this->resourceMap[get_class($model)]($model, $this);

        return $this->resourceInstances[$hash] = $resourceInstance;
    }

    /**
    * Magic call to create element object
    *
    * @inheritdoc
    */
    final public function __call($method, $params)
    {
        if (preg_match('/make+/i', $method)) {
            
            $class = substr($method, 4);
            $class = '\\HackerBoy\\JsonApi\\Elements\\'.$class;
            $data = isset($params[0]) ? $params[0] : [];

            if (class_exists($class)) {
                return new $class($data, $this);
            }

        }

        throw new Exception('Method '.$method.' does not exist');
    }

    

}