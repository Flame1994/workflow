<?php
namespace Rhaarhoff\Workflow\Helpers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\Exception\UnexpectedTypeException;

/**
 * @author Ruan Haarhoff <ruan@aptic.com>
 * @since 20200208 Initial creation.
 */
class Utility
{
    /**
     * Type constants.
     */
    const TYPE_STRING = 'string';

    /**
     * File constants.
     */
    const FILE_TYPE_JSON = 'json';
    const FILE_INFO_EXTENSION = 'extension';

    /**
     * @param $text
     */
    public static function assertIsString($text)
    {
        static::assertIsType($text, self::TYPE_STRING);
    }

    /**
     * @param $input
     * @param $expectedType
     */
    private static function assertIsType($input, $expectedType)
    {
        if (gettype($input) === $expectedType) {
            // Do nothing
        } else {
            throw new UnexpectedTypeException($input, $expectedType);
        }
    }

    /**
     * @param $text
     */
    public static function assertIsAlphaNumeric($text)
    {
        static::assertIsString($text);

        if (preg_match('([^A-Za-z0-9_/\\\\])', $text)) {
            throw new InvalidArgumentException('Field must be an alphanumeric string.');
        } else {
            // Do nothing
        }
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public static function formatTextToSnakeCase(string $text): string
    {
        $folderName = preg_replace('/(?<=\\w)(?=[A-Z])/',"_$1", $text);

        return strtolower($folderName);
    }

    /**
     * @param string $filePath
     *
     * @throws FileNotFoundException
     */
    public static function assertFileExists(string $filePath)
    {
        if (static::fileExists($filePath)) {
            // Do nothing
        } else {
            throw new FileNotFoundException();
        }
    }

    /**
     * @param string $filePath
     *
     * @return bool
     */
    public static function fileExists(string $filePath): bool
    {
        return file_exists($filePath);
    }

    /**
     * @param string $filePath
     *
     * @return array|false
     * @throws FileNotFoundException
     */
    public static function getAllFileInPath(string $filePath)
    {
        static::assertFileExists($filePath);

        $allFileIncludingParent = scandir($filePath);

        $allFileExcludingParent = [];

        foreach ($allFileIncludingParent as $file) {
            if ($file != '.' && $file != '..') {
                $allFileExcludingParent[] = $file;
            }
        }

        return $allFileExcludingParent;
    }

    /**
     * @param array $input
     *
     * @return bool
     */
    public static function isArrayEmpty(array $input): bool
    {
        return empty($input);
    }

    /**
     * @param string $filePath
     *
     * @return bool
     * @throws FileNotFoundException
     */
    public static function isFileValidJson(string $filePath): bool
    {
        static::assertFileExists($filePath);

        if (static::isFileExtensionTypeJson($filePath) && static::isFileContentValidJson($filePath)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $filePath
     *
     * @return bool
     */
    private static function isFileExtensionTypeJson(string $filePath): bool
    {
        $fileInfo = pathinfo($filePath);

        return $fileInfo[self::FILE_INFO_EXTENSION] === self::FILE_TYPE_JSON;
    }

    /**
     * @param string $filePath
     *
     * @return bool
     */
    private static function isFileContentValidJson(string $filePath): bool
    {
        $fileContents = file_get_contents($filePath);

        if (is_null(json_decode($fileContents))) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param array $fieldContent
     * @return int
     */
    public static function countNumberOfElementInArray(array $fieldContent): int
    {
        return count($fieldContent);
    }
}
