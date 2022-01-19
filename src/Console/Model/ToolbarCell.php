<?php

namespace Sourceability\ConsoleToolbarBundle\Console\Model;

class ToolbarCell
{
    /**
     * @var string
     */
    private $text;

    /**
     * @var string|null
     */
    private $color;

    public function __construct(string $text, ?string $color = null)
    {
        $this->text = $text;
        $this->color = $color;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }
}
