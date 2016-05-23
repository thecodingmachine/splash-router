<?php

namespace Mouf\Mvc\Splash\Annotations;

/**
 * @Annotation
 * @Target({"METHOD"})
 * @Attributes({
 *   @Attribute("value", type = "string")
 * })
 */
class URL
{
    /**
     * @var string
     */
    private $url;

    public function __construct(array $values)
    {
        $this->url = $values['value'];
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
