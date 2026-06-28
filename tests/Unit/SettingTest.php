<?php

namespace Tests\Unit;

use App\Models\Setting;
use PHPUnit\Framework\TestCase;

class SettingTest extends TestCase
{
    public function test_setting_converts_values_to_declared_type(): void
    {
        $boolean = new Setting(['type' => 'boolean', 'value' => '1']);
        $integer = new Setting(['type' => 'integer', 'value' => '16']);
        $json = new Setting(['type' => 'json', 'value' => '{"enabled":true}']);

        $this->assertTrue($boolean->typedValue());
        $this->assertSame(16, $integer->typedValue());
        $this->assertSame(['enabled' => true], $json->typedValue());
    }
}
