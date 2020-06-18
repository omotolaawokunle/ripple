<?php

namespace Ripple\QueryBuilder;

class QueryFactory
{
    public static function isAllColumns($columns): bool
    {
        return (bool) $columns == ['*'];
    }
}
