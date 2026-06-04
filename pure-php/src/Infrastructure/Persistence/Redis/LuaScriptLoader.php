<?php

declare(strict_types=1);

namespace HexagonPractise\Infrastructure\Persistence\Redis;

final class LuaScriptLoader
{
    /** @var array<string, string> */
    private static array $cache = [];

    public static function load(string $filename): string
    {
        if (!isset(self::$cache[$filename])) {
            $path = __DIR__ . '/Lua/' . $filename;
            if (!is_readable($path)) {
                throw new \RuntimeException(sprintf('Lua script not found: %s', $path));
            }
            self::$cache[$filename] = (string) file_get_contents($path);
        }

        return self::$cache[$filename];
    }
}
