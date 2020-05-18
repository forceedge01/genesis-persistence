<?php

namespace Genesis\Services\Persistence\Contracts;

/**
 * Store interface.
 */
interface StoreInterface
{
    public function save(string $table, array $values): int;

    public function get(string $table, array $where, array $order = ['id' => 'asc']);

    public function getAll(string $table, array $order = ['id' => 'asc']);

    public function getSingle(string $table, array $where, array $order = ['id' => 'asc']);

    public function delete(string $table, array $where);
}
