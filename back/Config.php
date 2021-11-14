<?php

namespace Config;

class Config{
    public const DISPLAY_ERRORS = true;

    public const PS = "/";
    public const DS = DIRECTORY_SEPARATOR;

    public const DOMAIN = "localhost";
    public const COMPLETE_HOST_NAME = "//" . self::DOMAIN;

    public const ROUTER_BASE_PATH = self::PS . "api";
    public const CONFIG_ROUTES_PATH = "." . self::PS . "routes.php";

    public const WHERE = "API";

    public const NEW_LINE = "<br>";
    public const NL = self::NEW_LINE;

    public const DEBUG = false;

    public const RETURN_DEBUG_DATA = true;

    public const HOST = "localhost";
    public const NAME = "todoslist";
    public const USER = "todoslist";
    public const PASSWORD = "todoslist";
    public const DB_SCHEMA = "";
 
    public const NAMESPACE = "src\\Domain\\";

    public const JWT_SECRET = "-JaNdS4RgUkXp2s+5v8y/A?D(G+KbPeShVm";
    public const JWT_ALGORITHM = ["HS256"];
}