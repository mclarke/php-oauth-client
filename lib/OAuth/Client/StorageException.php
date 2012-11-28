<?php

namespace OAuth\Client;

class StorageException extends \Exception
{
    private $_description;

    public function __construct($message, $description = NULL, $code = 0, \Exception $previous = null)
    {
        $this->_description = $description;
        parent::__construct($message, $code, $previous);
    }

    public function getDescription()
    {
        return $this->_description;
    }

}
