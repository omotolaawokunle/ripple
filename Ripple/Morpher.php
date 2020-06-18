<?php

namespace Ripple;

class Morpher
{
    public function __invoke($class, $object)
    {
        $class = new \ReflectionClass($class);
        $entity = $class->newInstanceWithoutConstructor();

        foreach ($class->getProperties() as $prop) {
            if (array_key_exists($prop->getName(), $object)) {
                $prop->setValue($entity, $object[$prop->getName()]);
            } else {
                if (!$prop->isStatic()) {
                    $entity->removeAttribute($prop->getName());
                }
            }
        }
        return $entity;
    }
}
