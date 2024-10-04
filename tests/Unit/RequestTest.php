<?php

namespace Kuza\Krypton\Tests\Unit;

use Kuza\Krypton\Classes\Data;
use Kuza\Krypton\Classes\Requests;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase {

    /**
     * @var Requests
     */
    protected $request;

    /**
     * Set up the test case.
     */
    public function setUp(): void {
        $this->request = new Requests();
    }


    /**
     * test mapping array to an object
     */
    public function testQueryParam() {
        $uri = "directory_uuid=818a6b77-8f52-453f-ab0e-fa6099eb5be9&offset=0&limit=10&entities=%5B%7B%22entity_name%22:%22items.*.%20item_bar_code%22,%22original_value%22:%226161106614707%22%7D%5D";
        $this->request->setQueryParameters($uri);

        print_r($this->request->filters);
    }
}
