<?php
namespace Nf\Middleware;

interface MiddlewareInterface
{

    // this function needs to return nothing or true to allow the rest of the code to be executed
    public function execute();
}
