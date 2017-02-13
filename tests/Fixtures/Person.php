<?php declare(strict_types=1);

namespace Kassko\Util\ConfigTest\Fixtures;

class Person
{
    /** @var string */
    private $firstname;
    /** @var Kassko\Util\ConfigTest\Fixtures\Address */
    private $address;
    /** @var array */
    private $phones;
    /** @var Kassko\Util\ConfigTest\Fixtures\Address[] */
    private $fallbackAddresses;

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname)
    {
        $this->firstname = $firstname;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress(Address $address)
    {
        $this->address = $address;
    }

    public function getPhones()
    {
        return $this->phones;
    }

    public function addPhonesItem(string $phone)
    {
        $this->phones[] = $phone;
    }

    public function getFallbackAddresses()
    {
        return $this->fallbackAddresses;
    }

    public function addFallbackAddressesItem(Address $address)
    {
        $this->fallbackAddresses[] = $address;
    }
}
