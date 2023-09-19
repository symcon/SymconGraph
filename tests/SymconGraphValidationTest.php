<?php

declare(strict_types=1);
include_once __DIR__ . '/stubs/Validator.php';
class SymconGraphValidationTest extends TestCaseSymconValidation
{
    public function testValidateWebGraph(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }
    public function testValidateWebGraphModule(): void
    {
        $this->validateModule(__DIR__ . '/../WebGraph');
    }
}