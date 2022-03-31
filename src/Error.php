<?php

namespace Carteni\ToDo2;

class Error
{
    public function throwError(string $message) {
        throw new \LogicException($message);
    }

}