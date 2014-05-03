<?php namespace League\Fractal\Serializer;

class JsonApiSerializer extends ArraySerializer
{

    /**
     * Serialize the top level data.
     * 
     * @param array $data
     * 
     * @return array
     */
    public function serializeData($resourceKey, array $data)
    {
        if (count($data) == count($data, COUNT_RECURSIVE)) {
            $data = array($data);
        }

        return array($resourceKey => $data);
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
        if (empty($data)) {
            return array();
        }

        $response = array('linked' => array());

        foreach ($data as $key => $value) {
            $response['linked'] = array_merge($response['linked'], $value);
        }

        return $response;
    }

    /**
     * Serialize the available embeds.
     * 
     * @param  array  $embeds
     * @return array
     */
    public function serializeAvailableEmbeds(array $embeds)
    {
        return array();
    }
}
