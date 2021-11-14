<?php

namespace TodosList\Infraestructure\Database\Interfaces;

interface Database
{
    public function __construct($host = "localhost", $name = "myDataBaseName", $user = "root", $password    = "");
    public function Insert($statement = "", $parameters = []): string;
    public function Select($statement = "", $parameters = []): array;
    public function Update($statement = "", $parameters = []): void;
    public function Remove($statement = "", $parameters = []): void;
}
