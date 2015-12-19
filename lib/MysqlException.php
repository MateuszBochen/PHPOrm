<?php

namespace BlockBlog;

class MysqlException extends \Exception
{
    public function __construct($message, $array = [])
    {
        $msg = $message.'<hr /><pre>';
        $msg .= print_r($array, 1);
        $msg .= '</pre>';
        parent::__construct($msg);
    }
}
