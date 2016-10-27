<?php

namespace Genesis\Services\Persistence\Contracts;

/**
 * MaperInterface interface.
 */
interface MapperInterface
{
    public function createTable($class);

    public function delete($class, array $where = []);

    public function persist($object);

    public function get($class, array $args = [], $order = 'asc');

    public function getSingle($class, array $args = [], $order = 'asc');
}
