# Current version: 2.x

# Packagist: hackerboy/json-api
Making JSON API implementation (server side) easiest for you 
## Install
```
composer require hackerboy/json-api
```

# Run examples:
- Example code: /example/index.php
- Guide to set up /examples/index.php to see some examples of uses.

```
git clone https://github.com/hackerboydotcom/json-api ./hackerboy-json-api;
cd hackerboy-json-api;
composer install --dev;
```
Then config your localhost nginx/apache to access [LOCALHOST_PATH]/examples/index.php

# Table of Contents
- [How to use? (Making response document)](#how-to-use)
    - [Create your resource schema](#create-your-resource-schema)
    - [Configuration and mapping your resources](#configuration-and-mapping-your-resources)
    - [Document methods](#document-methods)
    - [Implement relationships](#implement-relationships)
    - [Set data as relationships](#set-data-as-relationships)
    - [toArray() and toJson() methods](#toarray-and-tojson-methods)
    - [Easily create elements for your document](#easily-create-elements-for-your-document)
        - [Create errors](#create-errors)
        - [Create links](#create-links)
        - [Create other elements](#create-other-elements)
    - [Document query](#document-query)
        - [Examples of document query](#examples-of-document-query)

- [Client-Side usages](#client-side-usages)
    - [Flexible document and resources](#flexible-document-and-resources)
        - [Example of flexible document](#example-of-flexible-document)
        - [Parse from string or array](#parse-from-string-or-array)

# How to use?
## Create your resource schema
Mapping your id, type, attributes from your model objects by creating Schema classes. Inside the Schema class, you can retrieve your model object through `$this->model`.
For example, I'm gonna talk in a Laravel project context. Firstly, let's create a resource file: /app/Http/JsonApiResources/UserResource.php

```
<?php
namespace App\Http\JsonApiResources;
use HackerBoy\JsonApi\Abstracts\Resource;

class UserResource extends Resource {

    protected $type = 'users';

    public function getId()
    {
        // $this->model is the instance of model, in this case, it's App\User
        return $this->model->id;
    } 

    public function getAttributes()
    {
        return [
            'name' => $this->model->name,
            'email' => $this->model->email
        ];
    }

    /**
    * Meta is optional
    */
    public function getMeta()
    {
        return [
            'meta-is-optional' => $this->model->some_value
        ];
    }
}
```

## Configuration and mapping your resources
Now we can easily generate a JSON API document object like this:

```
<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use HackerBoy\JsonApi\Document;

class UserController extends Controller {
  
    public function index()
    {
        // User to return
        $user = \App\User::find(1);
        $users = \App\User::take(10)->get();
        
        // Config and mapping
        $config = [
            'resource_map' => [
                \App\User::class => \App\Http\JsonApiResources\UserResource::class
                // Map your other model => resource
            ],
            'api_url' => 'http://example.com',
            'auto_set_links' => true, // Enable this will automatically add links to your document according to JSON API standard
        ];
         
        // Let's test it
        $document = new Document($config);
        $document->setData($user) // or set data as a collection by using ->setData($users)
                  ->setMeta(['key' => 'value']);
        
        return response()->json($document)->header('Content-Type', 'application/vnd.api+json');
    }
}

```

## Document methods

The difference between "set methods" and "add methods" are is: "Set methods" will override the data while "add methods" will append to data.

Available "set" methods from $document object are: 
+ setData($resourceOrCollection, $type = 'resource') // Default $type = 'resource', in case you need to return data as relationship object, change $type to 'relationship'. Or you can use the setDocumentType() method below
+ setDocumentType($type) // $type = "resource" or "relationship".
+ setIncluded($resourceOrCollection)
+ setErrors($errors) // Array or HackerBoy\JsonApi\Elements\Error object - single error or multiple errors data will both work for this method
+ setLinks($links) // Array of link data or HackerBoy\JsonApi\Elements\Links object
+ setMeta($meta) // Array of meta data or HackerBoy\JsonApi\Elements\Meta object

Available "get" methods from $document object are: 
+ getQuery() // Get [Query](#document-query) object for finding resources
+ getConfig() // Get document config
+ getData() // Get document data
+ getIncluded() // Get document included data
+ getErrors() // Get document errors data
+ getMeta() // Get document meta data
+ getLinks() // Get document links
+ getUrl($path = '') // Get api url
+ getResourceInstance($modelObject) // Get resource instance of a model object

Available "add" methods from $document object are:
+ addIncluded($resourceOrCollection)
+ addErrors($errors) // Array or HackerBoy\JsonApi\Elements\Error object - single error or multiple errors data will both work for this method
+ addLinks($links) // Array of link data or HackerBoy\JsonApi\Elements\Links object
+ addMeta($meta) // Array of meta data or HackerBoy\JsonApi\Elements\Meta object

Example:
```
<?php

$document->setData([$post1, $post2]) // or ->setData($post) will also work
    ->setIncluded([$comment1, $comment2])
    ->setMeta([
            'meta-key' => 'meta-value',
            'meta-key-2' => 'value 2'
        ])
    ->setLinks($document->makePagination([
            'first' => $document->getUrl('first-link'),
            'last' => $document->getUrl('last-link'),
            'prev' => $document->getUrl('prev-link'),
            'next' => $document->getUrl('last-link'),
        ]));

// Get document data
$documentData = $document->getData();
```

## Implement relationships
Simply return an array in getRelationships() method

```
<?php

namespace HackerBoy\JsonApi\Examples\Resources;

use HackerBoy\JsonApi\Abstracts\Resource;

class PostResource extends Resource {

    protected $type = 'posts';

    public function getId()
    {
        return $this->model->id;
    } 

    public function getAttributes()
    {
        return [
            'title' => $this->model->title,
            'content' => $this->model->content
        ];
    }

    public function getRelationships()
    {
        $relationships = [
            'author' => $this->model->author, // Post has relationship with author

            // If post has comments, return a collection
            // Not? Return a blank array (implement empty to-many relationship)
            'comments' => $this->model->comments ? $this->model->comments : []
        ];

        return $relationships;
    }
}
```

## Set data as relationships
Second param allows you to set data as relationship (for request like: /api/posts/1/relationships/comments)
```
<?php

// Set data as relationship
$document->setData($resourceOrCollection, 'relationship');

// Or
$document->setData($resourceOrCollection);
$document->setDocumentType('relationship');
```

## toArray() and toJson() methods
New methods in v1.1. Available for document, elements and resources
```
<?php
$data = $document->toArray();
$json = $document->toJson();
```

## Easily create elements for your document
Suppose that we created a $document object

### Create errors
```
<?php

// Create an error
$errorData = [
    'id' => '123',
    'status' => '500',
    'code' => '456',
    'title' => 'Test error'
];

// Return an error
$error = $document->makeError($errorData);

// Return multiple errors
$errors = [$document->makeError($errorData), $document->makeError($errorData)];

// Attach error to document
$document->setErrors($error);
// Or
$document->setErrors($errors);
// It'll even work if you just put in an array data
$document->setErrors($errorData);

```

### Create links
```
<?php

$linkData = [
        'self' => $document->getUrl('self-url'),
        'ralated' => $document->getUrl('related-url')
    ];

// Create links
$links = $document->makeLinks($linkData);

// Attach links to document
$document->setLinks($links);
// this will also work
$document->setLinks($linkData);

// Create pagination
$pagination = $document->makePagination([
        'first' => $document->getUrl('first-link'),
        'last' => $document->getUrl('last-link'),
        'prev' => $document->getUrl('prev-link'),
        'next' => $document->getUrl('last-link'),
    ]);

// Attach pagination to document
$document->setLinks($pagination);
```

### Create other elements
It'll work the same way, available methods are: 

+ makeError(), 
+ makeErrorResource()
+ makeLink() (Create link object inside links data: https://jsonapi.org/format/#document-links)
+ makeLinks()
+ makePagination() (Make special Links object with pagination standard required)
+ makeMeta()
+ makeRelationship() (Create relationship object inside relationships data: https://jsonapi.org/format/#document-resource-object-relationships)
+ makeRelationships()

You can see more examples in /examples/index.php

## Document Query

New feature in v2, now you can make query to find resources (HackerBoy\JsonApi\Abstracts\Resource) in a document by using `$document->getQuery()` method. 
`$document->getQuery()` return an instance of Laravel [Illuminate\Support\Collection](https://laravel.com/docs/collections) (Check the link for full document of querying methods).

Objects returned by query are `\HackerBoy\JsonApi\Abstracts\Resource`

### Example of document query

```
<?php

$findResourceById = $document->getQuery()->where('type', '...')->where('id', '...')->first();
$findResourceByAttributes = $document->getQuery()->where('attributes.title', '...')->first();

```

# Client-Side usages

## Flexible document and resources
- Flexible document can be used exactly like normal document, but $config is optional, flexible resource allowed... You can consider it as a "free schema" version of document.
- Flexible document can use the same $config as normal document, then you can use it to work with flexible resource and mapped resource in the same document.
- Flexible document might be helpful for projects with no ORM, build JSON API data quickly without configuration, build JSON API data to POST to another JSON API endpoint...
- Flexible document is not recommended anyway, as it allows to build a document in a free way and may cause unpredicted errors to your API like missing elements, invalid format...etc... So use it carefully and wisely

### Example of flexible document

```
<?php

$flexibleDocument = new HackerBoy\JsonApi\Flexible\Document; // $config is the same format with normal document but it is optional

// Create a flexible resource
$flexibleResource = $flexibleDocument->makeFlexibleResource();
$flexibleResource->setId(1234);
                ->setType('flexible')
                ->setAttributes([
                        'attribute_name' => 'attribute value'
                    ])
                ->setMeta([
                        'meta-key' => 'meta value'
                    ])
                ->setLinks([
                        'self' => '/link'
                    ]);

// Or with faster way to set type and id
$flexibleResource = $flexibleDocument->makeFlexibleResource('resource-type', 'resource-id');

// Attach flexible resource to document
$flexibleDocument->setData($flexibleResource); // You can put in a collection as well, all other methods are the same

echo $flexibleDocument->toJson();

```

### Parse from string or array

Parse from string

```
<?php

use HackerBoy\JsonApi\Helpers\Validator;

$jsonapiString = '{
    "data": {
      "type": "articles",
      "id": "1",
      "attributes": {
        "title": "Rails is Omakase"
      },
      "relationships": {
        "author": {
          "links": {
            "self": "/articles/1/relationships/author",
            "related": "/articles/1/author"
          },
          "data": { "type": "people", "id": "9" }
        },
        "images": {
            "data": [
                {
                    "type": "images",
                    "id": "1"
                },
                {
                    "type": "images",
                    "id": "2"
                }
            ]
        }
      }
    },
    "included": [
        {
            "type": "people",
            "id": "1",
            "attributes": {
                "name": "John Doe"
            }
        },
        {
            "type": "images",
            "id": "1",
            "attributes": {
                "src": "http://example.com/1.jpg"
            }
        },
        {
            "type": "images",
            "id": "2",
            "attributes": {
                "src": "http://example.com/2.jpg"
            }
        }
    ]
}';

// Helper method to validate request JSON:API string
if (!Validator::isValidRequestString($jsonapiString)) {
    throw new Exception('Invalid request string');
}

// Helper method to validate response JSON:API string
if (!Validator::isValidResponseString($jsonapiString)) {
    throw new Exception('Invalid response string');
}

$document = FlexibleDocument::parseFromString($jsonapiString);

// Get primary data
$article = $document->getData();

// Get article's images and author
$articleAuthor = $article->getRelationshipData('author');
$articleImages = $article->getRelationshipData('images');

// Find the people resource
$peopleResource = $document->getQuery()->where(['type' => 'people', 'id' => '1'])->first();

if ($articleAuthor === $peopleResource) {
    echo 'Great! They are the same';
}

// Get data
echo $peopleResource->getId(); // Expect '1'
echo $peopleResource->getType(); // Expect 'people'
echo $peopleResource->getAttribute('name'); // Expect 'John Doe'
echo $peopleResource->getAttribute('not-found-attribute', 'Default value'); // Expect 'Default value'
var_dump($peopleResource->getAttributes()); // Expect ['name' => 'John Doe']

// Modify resource data
$peopleResource->setAttribute('email', 'example@example.com');

// Check document after modification
echo $document->toJson();
```