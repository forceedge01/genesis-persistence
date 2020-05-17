<?php

namespace Genesis\Services\Persistence\Contracts;

/**
 * MaperInterface interface.
 */
interface MapperInterface
{
    public function createTable(string $class);

    public function delete($class, array $where = []);

    public function persist(ModelInterface $object);

    public function get(string $class, array $args = [], array $order = ['id' => 'asc']);

    public function getSingle(string $class, array $args = [], array $order = ['id' => 'asc']);

    public function getAssociated(string $associatedClass, ModelInterface $object);
}
