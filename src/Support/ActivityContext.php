<?php

namespace Gottvergessen\Logger\Support;


class ActivityContext
{
    protected static ?string $requestId = null;

    protected static ?string $batchId = null;

    protected static ?string $causerType = null;
    protected static ?int $causerId = null;
    protected static array $meta = [];

    public static function setRequestId(string $id): void
    {
        static::$requestId = $id;
    }

    public static function requestId(): ?string
    {
        return static::$requestId;
    }

    public static function setOrigin(string $origin): void
    {
        static::$origin = $origin;
    }

    public static function addMeta(array $meta): array
    {
        static::$meta = array_merge(static::$meta, $meta);
        return static::$meta;
    }


    public static function meta(): array
    {
        return static::$meta;
    }

    public static function setBatchId(string $id): void
    {
        static::$batchId = $id;
    }

    public static function batchId(): ?string
    {
        return static::$batchId;
    }

    public static function setCauser(?string $type, ?int $id): void
    {
        static::$causerType = $type;
        static::$causerId   = $id;
    }


    public static function causerType(): ?string
    {
        return static::$causerType;
    }

    public static function causerId(): ?int
    {
        return static::$causerId;
    }

    public static function flush(): void
    {
        static::$requestId = null;
        static::$batchId   = null;
        static::$origin    = null;
        static::$causerType = null;
        static::$causerId   = null;
        static::$meta      = [];
    }
}
