<?php declare(strict_types=1);

namespace Kassko\Util\ConfigTest\Fixtures;

class Address
{
    /** @var string */
    private $state;
    /** @var string */
    private $country;

    public function getState()
    {
        return $this->state;
    }

    public function setState(string $state)
    {
        $this->state = $state;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry(string $country)
    {
        $this->country = $country;
    }
}
