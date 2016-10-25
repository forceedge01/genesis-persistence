<?php

namespace Genesis\Services\Persistence\Contracts;

/**
 * Store interface.
 */
interface StoreInterface
{
    public function save($table, array $values);

    public function get($table, array $where);

    public function getSingle($table, array $where, $orderBy);

    public function delete($table, array $where);
}
