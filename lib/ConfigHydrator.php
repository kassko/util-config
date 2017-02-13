<?php

namespace Kassko\Util\Config;

use Kassko\Util\Reflection\ReflectorRepository;

/*
methods:
    isRichWoman:
        name: isRichWoman
        expectations:
            -
                mocks:
                    - rich
                    - woman
        mocks_store:
            behaviour:
                type_: ret_val
                config_: ~
        spies_store: ~
*/

class ConfigHydrator
{
    /**
     * @var ReflectorRepository
     */
    private $reflectorRepo;

    public function __construct(ReflectorRepository $reflectorRepo)
    {
        $this->reflectorRepo = $reflectorRepo;
    }

    public function extractConfig(&$config, $object)
    {
        foreach ($object as $propName => $propValue) {

        }
    }

    public function hydrateCollection($config, $class)
    {
        $collection = [];

        foreach ($config as $configKey => $configItem) {
            $collection[$configKey] = $this->hydrateObject($configItem, new $class);
        }

        return $collection;
    }

    public function hydrateObject($config, $object)
    {
        $class = get_class($object);
        $reflClass = $this->reflectorRepo->reflClass($class);
        $advReflClass = $this->reflectorRepo->advReflClass($class);
        $accessorFinder = $this->reflectorRepo->accessorFinder($class);

        foreach ($config as $configKey => $configItem) {
            $propName = $this->toLowerCamelCase($configKey);

            $properties = $advReflClass->getPropertiesNames();
            //var_dump($properties, '[[', $propName, ']]');
            /*if (!isset($properties[$propName])) {
                continue;
            }*/

            $propDocParser = $this->reflectorRepo->propertyDocParser($class, $propName);
            $propDocParser->parse();
            //var_dump($propDocParser->getAllTags(), $class, $propName);
            $propType = $propDocParser->getTag('var')->getType();

            if ('[]' === substr($propType, -2)) {//If object collection property.
                $propType = substr($propType, 0, -2);
                $methods = $advReflClass->getMethodsNames();
                $propCollection = $this->hydrateCollection($configItem, $propType);

                if (null !== $setter = $accessorFinder->findPropSetter($propName)) {
                    $object->$setter($propCollection);
                } elseif (null !== $adder = $accessorFinder->findPropAdder($propName)) {
                    foreach ($propCollection as $propValue) {
                        $object->$adder($propValue);
                    }
                } elseif (null !== $adder = $accessorFinder->findPropAssocAdder($propName)) {
                    foreach ($propCollection as $propKey => $propValue) {
                        $object->$adder($propKey, $propValue);
                    }
                }
            } elseif ('array' === $propType) {//If scalar collection property.
                $propCollection = $configItem;
                if (null !== $setter = $accessorFinder->findPropSetter($propName)) {
                    $object->$setter($propCollection);
                } elseif (null !== $adder = $accessorFinder->findPropAdder($propName)) {
                    foreach ($propCollection as $propValue) {
                        $object->$adder($propValue);
                    }
                } elseif (null !== $adder = $accessorFinder->findPropAssocAdder($propName)) {
                    foreach ($propCollection as $propKey => $propValue) {
                        $object->$adder($propKey, $propValue);
                    }
                }
            } elseif (!$this->isBuiltinType($propType)) {//If single object property.
                $propValue = new $propType;
                $this->hydrateObject($configItem, $propValue);

                if (null !== $setter = $accessorFinder->findPropSetter($propName)) {
                    $object->$setter($propValue);
                }
            } else {//If scalar property.
                var_dump(get_class($object), $propName);
                if (null !== $setter = $accessorFinder->findPropSetter($propName)) {
                    $object->$setter($configItem);
                }
            }
        }

        return $object;
    }

    protected function isBuiltinType($type)
    {
        return in_array(
            $type, [
                'boolean',
                'integer',
                'int',
                'float',
                'string',
                'array',
                'object',
                'callable',
                'resource',
                'null',
                'mixed',
                'number',
                'callback',
                'array|object',
                'void',
            ]
        );
    }

    protected function toLowerCamelCase($dashCaseString)
    {
        return lcfirst(implode('', array_map('ucFirst', explode('_', $dashCaseString))));
    }

    protected function toUpperCamelCase($dashCaseString)
    {
        return ucFirst(implode('', array_map('ucFirst', explode('_', $dashCaseString))));
    }

    /*public function hydrate($config)
    {
        if (isset($config['type_'])) {
            if (!isset($this->rootNamespace)) {
                throw new \RuntimeException(
                    sprintf(
                        'Cannot hydrate data in the config key "%s".'
                        . ' You must provide either the namespace root of your model classes.'
                        . ' or the config key "class"',
                        get_class($configKey)
                    )
                );
            }

            if (!isset($config['config_'])) {
                throw new \RuntimeException(
                    sprintf(
                        'Cannot hydrate data in the config key "%s".'
                        . ' You must provide a section "_config".',
                        get_class($configKey)
                    )
                );
            }

            $class = $this->rootNamespace . $this->toUpperCamelCase($config['type_']);

            $properties = array_flip($this->reflectorRepo->getPropertiesNames(get_class($class)));
            $methods = array_flip($this->reflectorRepo->getMethodsNames(get_class($class)));

            $instance = new $class;
            foreach ($config['config_'] as $configKey => $configItem) {

                $propName = $this->toLowerCamelCase($configKey);
                if (!isset($properties[$propName])) {
                    continue;
                }

                $propData = $this->hydrate($configItem);
                $setter  = $this->findSetter($propName, $object);
                $object . $setter($propInstance);

                //$propClass = $this->reflectorRepo->getPropertyType($propName);
            }
        } elseif (is_array($config)) {

        }
    }*/
}
