<?php

namespace Garbetjie\Http\RequestLogging\Context {

    use Garbetjie\Http\RequestLogging\Tests\HeaderBag;

    function header(string $name) {
        HeaderBag::add($name);
    }

    function headers_list(): array {
        return HeaderBag::list();
    }

    function header_remove() {
        HeaderBag::clear();
    }
}

namespace Garbetjie\Http\RequestLogging\Tests\Context {

    use Garbetjie\Http\RequestLogging\Tests\HeaderBag;

    function header(string $name) {
        HeaderBag::add($name);
    }

    function headers_list(): array {
        return HeaderBag::list();
    }

    function header_remove() {
        HeaderBag::clear();
    }
}

namespace Garbetjie\Http\RequestLogging\Tests {
    function header(string $name) {
        HeaderBag::add($name);
    }

    function headers_list(): array {
        return HeaderBag::list();
    }

    function header_remove() {
        HeaderBag::clear();
    }
}
