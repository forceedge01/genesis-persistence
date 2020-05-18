<?php

namespace Genesis\Services\Persistence\Contracts;

/**
 * MaperInterface interface.
 */
interface MapperInterface
{
    public function createTable(string $class): array;

    public function delete(string $class, array $where = []): MapperInterface;

    public function persist(ModelInterface $object): ModelInterface;

    public function get(string $class, array $args = [], array $order = ['id' => 'asc']): array;

    public function getSingle(string $class, array $args = [], array $order = ['id' => 'asc']): ?ModelInterface;

    public function getAssociated($associatedClass, ModelInterface $object): ?ModelInterface;
}
