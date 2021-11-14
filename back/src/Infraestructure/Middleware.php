<?php

namespace TodosList\Infraestructure;

class Middleware{

    private $middlewareServices = [];

    public function getService(string $name): object{
        if(array_key_exists($name, $this->middlewareServices)){
            return $this->middlewareServices[$name];
        } else {
            return false;
        }
    }

    public function getAllServices(): array{
        return $this->middlewareServices;
    }

    public function setService(string $name, object $service): void {
        $this->middlewareServices[$name] = $service;
    }

    public function __toString()
    {
        return "Middleware Class with this Services: <br> " . print_r($this->middlewareServices, true);
    }
}