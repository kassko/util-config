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

    public function extractConfigFromCollection(&$config, $collection)
    {
        foreach ($collection as $key => $object) {
            $this->extractConfigFromObject($collection[$key], new $object);
        }
    }

    public function extractConfigFromObject(&$config, $object)
    {
        foreach ($object as $propName => $propValue) {
            $propDocParser = $this->reflectorRepo->propDocParser($object, $propName);

            if (is_array($propValue)) {
                if (0 === current($propValue)) {//Case normally impossible, it means more than 1 nested array.
                    $this->extractConfigFromCollection($config[$key], $object);
                } else {
                    $key = self::toDashCase($propName);
                    $this->extractConfigFromObject($config[$kay], $propValue);
                }
            } else {
                //If has a base class or interface, add _type, _config, _class
                $key = self::toDashCase($propName);
                $config = [];
                if (null !== $tag = $propDocParser->getTag('config_type')) {
                    if (2 !== $tag->getFieldsCount()) {
                        throw new \LogicException(
                            sprintf(
                                'Cannot extract properly data from property "%s::%s"'
                                'The annotation @config_type should contains one argument (the config type)',
                                get_class($object),
                                $propName
                            )
                        );
                    }

                    $config[$key]['_type'] = $tag->getField(1);
                    $config[$key]['_config'] = [];
                } else {
                    $config[$key] = $propValue;
                }
            }
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
            $propName = self::toLowerCamelCase($configKey);

            $properties = $advReflClass->getPropertiesNames();
            //var_dump($properties, '[[', $propName, ']]');
            /*if (!isset($properties[$propName])) {
                continue;
            }*/

            $propDocParser = $this->reflectorRepo->propertyDocParser($class, $propName);
            $propDocParser->parse();
            //var_dump($propDocParser->getAllTags(), $class, $propName);
            $propType = $propDocParser->getTag('var')->getType();

            if ($propType->isObjectCollection()) {//If object collection property.
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
            } elseif ($propType->isCollection()) {//If scalar collection property.
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
            } elseif ($propType->isClass()) {//If single object property.
                $specialsKeysCount = 0;
                if (isset($configItem['_type'])) {
                    $specialsKeysCount++;
                }
                if (isset($configItem['_config'])) {
                    $specialsKeysCount++;
                }
                if (isset($configItem['_class'])) {
                    $specialsKeysCount++;
                }

                if (0 !== $nb && 3 !== $nb) {
                    throw new \LogicException(
                        'Cannot hydrate properly the config.'
                        PHP_EOL . 'You should specify either the 3 specials keys "_type", "_config", "_class"'
                        PHP_EOL . 'or none of these three keys.'
                    );
                }

                if (3 === $nb) {
                    $configItem = $configItem['_config'];
                    $propType = $configItem['_class'];
                }

                $propValue = new $propType;
                $this->hydrateObject($configItem, $propValue);

                if (null !== $setter = $accessorFinder->findPropSetter($propName)) {
                    $object->$setter($propValue);
                }
            } else {//If scalar property.
                if (null !== $setter = $accessorFinder->findPropSetter($propName)) {
                    $object->$setter($configItem);
                }
            }
        }

        return $object;
    }

    protected static function toDashCase($dashCaseString)
    {
        return strtolower(
            preg_replace(
                array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'), array('\\1_\\2', '\\1_\\2'), str_replace('_', '.', $dashCaseString)
            )
        );
    }

    protected static function toLowerCamelCase($dashCaseString)
    {
        return lcfirst(implode('', array_map('ucFirst', explode('_', $dashCaseString))));
    }

    protected static function toUpperCamelCase($dashCaseString)
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

                $propName = self::toLowerCamelCase($configKey);
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
