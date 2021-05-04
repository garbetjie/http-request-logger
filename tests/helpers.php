<?php

namespace Garbetjie\RequestLogging\Http\Context {

    use Garbetjie\RequestLogging\Http\Tests\HeaderBag;

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

namespace Garbetjie\RequestLogging\Http\Tests\Context {

    use Garbetjie\RequestLogging\Http\Tests\HeaderBag;

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

namespace Garbetjie\RequestLogging\Http\Tests {
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
