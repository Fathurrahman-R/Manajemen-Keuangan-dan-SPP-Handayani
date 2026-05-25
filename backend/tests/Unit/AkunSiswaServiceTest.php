<?php

namespace Tests\Unit;

use App\Services\AkunSiswaService;
use PHPUnit\Framework\TestCase;

class AkunSiswaServiceTest extends TestCase
{
    private AkunSiswaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AkunSiswaService();
    }

    public function test_generate_default_password_formats_date_as_ddmmyyyy(): void
    {
        $result = $this->service->generateDefaultPassword('2010-05-15');

        $this->assertEquals('15052010', $result);
    }

    public function test_generate_default_password_with_single_digit_day_and_month(): void
    {
        $result = $this->service->generateDefaultPassword('2008-01-03');

        $this->assertEquals('03012008', $result);
    }

    public function test_generate_default_password_with_end_of_year_date(): void
    {
        $result = $this->service->generateDefaultPassword('2015-12-31');

        $this->assertEquals('31122015', $result);
    }
}
