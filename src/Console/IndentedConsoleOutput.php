<?php

namespace Sourceability\ConsoleToolbarBundle\Console;

use Symfony\Component\Console\Output\ConsoleOutput;

class IndentedConsoleOutput extends ConsoleOutput
{
    /**
     * @var int
     */
    private $spaces;

    public function __construct(int $spaces)
    {
        parent::__construct();

        $this->spaces = $spaces;
    }

    protected function doWrite($message, $newline)
    {
        $prependBy = str_repeat(' ', $this->spaces);

        $message = $prependBy . $message;

        parent::doWrite($message, $newline);
    }
}
