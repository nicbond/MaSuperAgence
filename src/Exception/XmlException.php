<?php

namespace App\Exception;

class XmlException extends \Exception
{
    /**
     * @var array
     */
    private $errors;

    public function __construct($message, array $errors)
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
