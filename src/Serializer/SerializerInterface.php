<?php

namespace League\Fractal\Serializer;

use League\Fractal\Pagination\CursorInterface;
use League\Fractal\Pagination\PaginatorInterface;

interface SerializerInterface
{
    public function serializeData($resourceKey, array $data);
    public function serializeEmbeddedData($resourceKey, array $data);
    public function serializePaginator(PaginatorInterface $paginator);
    public function serializeCursor(CursorInterface $cursor);
    public function serializeAvailableEmbeds(array $embeds);
}
