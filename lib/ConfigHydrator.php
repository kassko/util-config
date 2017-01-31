<?php

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
     * @var string
     */
    private $rootNamespace;
    /**
     * @var Reflector
     */
    private $reflector;

    public function __construct($rootNamespace, Reflector $reflector)
    {
        $this->rootNamespace = $rootNamespace . '\\';
        $this->reflector = $reflector;
    }

    public function hydrateCollection($config, $class)
    {
        $collection = [];

        foreach ($config as $configKey => $configItem) {
            $collection[$configKey] = $this->hydrate($configItem);
        }

        return $collection;
    }

    public function hydrate($config, $object)
    {
        foreach ($config as $configKey => $configItem) {
            $propName = $this->toLowerCamelCase($configKey);
            $properties = array_flip($this->reflector->getproperties(get_class($object)));
            if (!isset($properties[$propName])) {
                continue;
            }

            $propType = $this->reflector->getPropertyType($propName);
            $methods = array_flip($this->reflector->getMethods(get_class($object)));

            if ('[]' === substr($propType, -2)) {//If collection property.
                $propCollection = $this->hydrateCollection($configItem, $propType);

                if (null !== $setter = $this->findSetter($object, $propName, $methods)) {
                    $object . $setter($propCollection);
                } elseif (null !== $adder = $this->findAdder($object, $propName, $methods)) {
                    $reflMethod = new ReflectionMethod($class, $name);
                    $nbParams = $reflMethod->getNumberOfParameters();
                    if (2 === $nbParams) {
                        foreach ($propCollection as $propKey => $propValue) {
                            $object . $adder($propKey, $propValue);
                        }
                    } elseif (1 === $nbParams) {
                        foreach ($propCollection as $propValue) {
                            $object . $adder($propValue);
                        }
                    } else {
                        throw new \RuntimeException(
                            sprintf(
                                'Cannot hydrate properly object "%s".'
                                . 'The adder method "%s::%s" must contains either one or 2 parameters".'
                                . ' Must look like either "%s::%s($value)" or "%s::%s($index, $value)"'
                                . ' This method actually contains %d parameters',
                                get_class($object),
                                get_class($object),
                                $adder,
                                $nbParams
                            )
                        );
                    }
                }
            } else {//If single object property
                $propValue = new $propType;
                $this->hydrate($configItem, $propValue);

                $setter = $this->findSetter($object, $propName, $methods);
                $object . $setter($propValue);
            }
        }
    }

    protected function findSetter($object, $propName, $methods)
    {
        $methodsBase = ucFirst($propName);

        switch (true) {
            case isset($setter = $methods['set' . $methodsBase]):
                return $setter;
            case isset($setter = $methods['make' . $methodsBase]):
                return $setter;
            case isset($setter = $methods['with' . $methodsBase]):
                return $setter;
        }

        return null;
    }

    protected function findAdder($object, $propName, $methods)
    {
        $methodsBase = substr(ucFirst($propName), 0, -1);

        switch (true) {
            case isset($setter = $methods['add' . $methodsBase]):
                return $setter;
            case isset($setter = $methods['push' . $methodsBase]):
                return $setter;
            case isset($setter = $methods['append' . $methodsBase]):
                return $setter;
        }

        return null;
    }

    protected function toLowerCamelCase($dashCaseString)
    {
        return implode('', array_map('ucFirst', explode('_', $dashCaseString)));
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

            $properties = array_flip($this->reflector->getPropertiesNames(get_class($class)));
            $methods = array_flip($this->reflector->getMethodsNames(get_class($class)));

            $instance = new $class;
            foreach ($config['config_'] as $configKey => $configItem) {

                $propName = $this->toLowerCamelCase($configKey);
                if (!isset($properties[$propName])) {
                    continue;
                }

                $propData = $this->hydrate($configItem);
                $setter  = $this->findSetter($propName, $object);
                $object . $setter($propInstance);

                //$propClass = $this->reflector->getPropertyType($propName);
            }
        } elseif (is_array($config)) {

        }
    }*/
}
