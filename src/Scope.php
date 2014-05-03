<?php

/*
 * This file is part of the League\Fractal package.
 *
 * (c) Phil Sturgeon <email@philsturgeon.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace League\Fractal;

use InvalidArgumentException;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\ResourceAbstract;
use League\Fractal\Pagination\CursorInterface;
use League\Fractal\Pagination\PaginatorInterface;

class Scope
{
    protected $availableEmbeds = array();

    protected $currentScope;

    protected $manager;

    protected $resource;

    protected $parentScopes = array();

    public function __construct(Manager $resourceManager, ResourceAbstract $resource, $currentScope = null)
    {
        $this->resourceManager = $resourceManager;
        $this->currentScope = $currentScope;
        $this->resource = $resource;
    }

    public function embedChildScope($scopeIdentifier, $resource)
    {
        return $this->resourceManager->createData($resource, $scopeIdentifier, $this);
    }

    /**
     * Getter for currentScope
     *
     * @return mixed
     */
    public function getCurrentScope()
    {
        return $this->currentScope;
    }

    /**
     * Getter for parentScopes
     *
     * @return mixed
     */
    public function getParentScopes()
    {
        return $this->parentScopes;
    }

    public function isRequested($checkScopeSegment)
    {
        if ($this->parentScopes) {
            $scopeArray = array_slice($this->parentScopes, 1);
            array_push($scopeArray, $this->currentScope, $checkScopeSegment);
        } else {
            $scopeArray = array($checkScopeSegment);
        }

        $scopeString = implode('.', (array) $scopeArray);

        $checkAgainstArray = $this->resourceManager->getRequestedScopes();

        return in_array($scopeString, $checkAgainstArray);
    }

    /**
     * Push a scope identifier into parentScopes
     *
     * @param string $newScope
     *
     * @return int Returns the new number of elements in the array.
     */
    public function pushParentScope($newScope)
    {
        return array_push($this->parentScopes, $newScope);
    }

    /**
     * Setter for parentScopes
     *
     * @param mixed $parentScopes Value to set
     *
     * @return self
     */
    public function setParentScopes($parentScopes)
    {
        $this->parentScopes = $parentScopes;

        return $this;
    }

    /**
     * Convert the current data for this scope to an array
     *
     * @return array
     */
    public function toArray()
    {
        list($data, $embeddedData) = $this->executeResourceTransformer();

        $serializer = $this->resourceManager->getSerializer();
        $resourceKey = $this->resource->getResourceKey();

        $data = $serializer->serializeData($resourceKey, $data);
        $embeddedData = $serializer->serializeEmbeddedData($resourceKey, $embeddedData);
        $availableEmbeds = $serializer->serializeAvailableEmbeds($this->availableEmbeds);

        $paginator = $cursor = array();

        if ($this->resource instanceof Collection) {
            if ($this->resource->hasPaginator()) {
                $paginator = $serializer->serializePaginator($this->resource->getPaginator());
            }

            if ($this->resource->hasCursor()) {
                $cursor = $serializer->serializeCursor($this->resource->getCursor());
            }
        }

        return array_merge_recursive($data, $embeddedData, $availableEmbeds, $paginator, $cursor);
    }

    /**
     * Convert the current data for this scope to JSON
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Execute the resources transformer and return the data and embedded data.
     * 
     * @return array
     */
    protected function executeResourceTransformer()
    {
        $transformer = $this->resource->getTransformer();
        $data = $embeddedData = array();

        if ($this->resource instanceof Item) {
            $data = $this->fireTransformer($transformer, $this->resource->getData());
            
            if ($this->transformerHasEmbeddedData($transformer)) {
                $embeddedData = $this->fireEmbeddedTransformers($transformer, $this->resource->getData());
            }
        } elseif ($this->resource instanceof Collection) {
            foreach ($this->resource->getData() as $item) {
                $data[] = $this->fireTransformer($transformer, $item);

                if ($this->transformerHasEmbeddedData($transformer)) {
                    $embeddedData[] = $this->fireEmbeddedTransformers($transformer, $item);
                }
            }
        } else {
            throw new InvalidArgumentException(
                'Argument $resource should be an instance of Resource\Item or Resource\Collection'
            );
        }

        return array($data, $embeddedData);
    }

    /**
     * Fire the main transformer.
     * 
     * @param  callable|\League\Fractal\TransformerAbstract  $transformer
     * @param  mixed  $data
     * @return array
     */
    protected function fireTransformer($transformer, $data)
    {
        if (is_callable($transformer)) {
            return call_user_func($transformer, $data);
        }

        return $transformer->transform($data);
    }

    /**
     * Fire the embedded transformers.
     * 
     * @param  \League\Fractal\TransformerAbstract  $transformer
     * @param  mixed  $data
     * @return array
     */
    protected function fireEmbeddedTransformers($transformer, $data)
    {
        $this->availableEmbeds = $transformer->getAvailableEmbeds();

        return $transformer->processEmbededResources($this, $data) ?: array();
    }

    /**
     * Determine if a transformer has any available embed data.
     * 
     * @param  callable|\League\Fractal\TransformerAbstract  $transformer
     * @return bool
     */
    protected function transformerHasEmbeddedData($transformer)
    {
        if ($transformer instanceof TransformerAbstract) {
            $availableEmbeds = $transformer->getAvailableEmbeds();

            return ! empty($availableEmbeds);
        }
        
        return false;
    }
}
