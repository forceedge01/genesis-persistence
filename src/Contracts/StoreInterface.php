<?php

namespace Genesis\Services\Persistence\Contracts;

/**
 * Store interface.
 */
interface StoreInterface
{
    public function save($table, array $values);

    public function get($table, array $where, array $order = ['id' => 'asc']);

    public function getAll($table, array $order = ['id' => 'asc']);

    public function getSingle($table, array $where, array $order = ['id' => 'asc']);

    public function delete($table, array $where);
}
