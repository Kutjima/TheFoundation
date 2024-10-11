<?php

/**
 * 
 */

namespace TheFoundation\RouterHttp\Response\Template;

/**
 * 
 */
class Table {

    /**
     * 
     */
    public function __tostring(): string
    {
        return $this->build();
    }

    /**
     * 
     */
    public function build(): string {
       
        return '';
    }
}
