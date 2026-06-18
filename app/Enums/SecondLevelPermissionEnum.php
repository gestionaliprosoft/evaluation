<?php

namespace App\Enums;

enum SecondLevelPermissionEnum: string
{
    case View = '';
    case Create = 'Create';
    case Update = 'Update';
    case Delete = 'Delete';
    case Download = 'Download';

    public function getPermissionString(): ?string
    {
        return "page_Browser{$this->value}";
    }
}
