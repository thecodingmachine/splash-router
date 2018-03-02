<?php

namespace TheCodingMachine\Splash\Annotations;

/**
 * @Annotation
 * @Target({"METHOD"})
 * @Attributes({
 *   @Attribute("value", type = "string")
 * })
 */
class Title
{
    /**
     * @var string
     */
    private $title;

    public function __construct(array $values)
    {
        $this->title = $values['value'];
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
