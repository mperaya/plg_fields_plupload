<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../plupload.php';

final class PluploadParseMimeExtTest extends TestCase
{
    public function testParseMimeExtReturnsCommaSeparatedString(): void
    {
        $mimeTypes = [
            (object) ['extensions' => 'jpg,jpeg,png'],
            (object) ['extensions' => 'pdf,zip'],
        ];

        $result = PlgFieldsPlupload::parseMimeExt($mimeTypes);

        self::assertSame('jpg,jpeg,png,pdf,zip', $result);
    }

    public function testParseMimeExtReturnsArrayFormatWhenRequested(): void
    {
        $mimeTypes = [
            (object) ['extensions' => 'mp4,mov'],
            (object) ['extensions' => 'avi'],
        ];

        $result = PlgFieldsPlupload::parseMimeExt($mimeTypes, 'array');

        self::assertSame(['mp4', 'mov', 'avi'], $result);
    }

    public function testParseMimeExtReturnsEmptyStringForEmptyInput(): void
    {
        $result = PlgFieldsPlupload::parseMimeExt([]);

        self::assertSame('', $result);
    }
}
