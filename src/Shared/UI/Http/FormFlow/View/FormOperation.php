<?php

namespace App\Shared\UI\Http\FormFlow\View;

enum FormOperation: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Filter = 'filter';
    case Command = 'process';
    case Index = 'index';

    public function past(): string
    {
        return match ($this) {
            self::Create => 'created',
            self::Update => 'updated',
            self::Delete => 'deleted',
            self::Filter => 'filtered',
            self::Command => 'processed',
            self::Index => 'indexed',
        };
    }
}
