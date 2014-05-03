<?php namespace League\Fractal\Serializer;

class DataArraySerializer extends ArraySerializer
{

    /**
     * Serialize the top level data.
     * 
     * @param  array  $data
     * @return array
     */
    public function serializeData($resourceKey, array $data)
    {
        return array('data' => $data);
    }

    /**
     * Serialize the embedded data.
     * 
     * @param  string  $resourceKey
     * @param  array  $data
     * @return array
     */
    public function serializeEmbeddedData($resourceKey, array $data)
    {
        return $this->serializeData($resourceKey, $data);
    }
}
