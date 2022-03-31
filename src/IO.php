<?php

namespace Carteni\ToDo2;

class IO
{
    function printLine($msg = ""): self
    {
        print $msg . PHP_EOL;
        return $this;
    }

    function readLine(string $msg): ?string
    {
        return readline($msg);
    }

}