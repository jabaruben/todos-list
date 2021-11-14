<?php

namespace src\Domain;

use TodosList\Infraestructure\Bootstrap;
use src\Infraestructure\HTTP\Response;
use src\Infraestructure\HTTP\ServerRequest;

class Users
{
    public function list(Bootstrap $app, Response $res, ServerRequest $req): Response
    {
        return $res->withJson(["pruebaa" => "pruebosa"], 200);
        // $arrResp = ["test" => "testing", "id" => $id];
        // $res->withJson($arrResp, 200)->send();
    }

    public function login(Bootstrap $app, Response $res, ServerRequest $req): Response
    {
        // echo "testing: " . password_hash("12345678", PASSWORD_DEFAULT ) . ConfigApi::NL;
        // if (password_verify($pass, $query->PASSWORD)) {
        $user = $req->getParam("user", null);
        $pass = $req->getParam("password", null);

        $db = $app->getMiddlewareService("db");
        $jwt = $app->getMiddlewareService("jwt");

        $sql = "SELECT * FROM Usuarios WHERE usuario = :user";
        $params = [":user" => $user];

        try {
            $query = $db->queryNextOne($sql, $params);
            $app->printScreenDebug($query);
            if (!$query) {
                $data["error"] = true;
                $data["message"] = "Error: The user specified does not exist.";
                return $res->withJson($data, 400);
            }

            $data["error"] = false;
            if (mb_strtoupper(md5($pass)) === mb_strtoupper($query->password)) {
                $data["token"] = $jwt::encode([$query->id, $query->usuario, $query->nombre], "secret");
                $data["message"] = "OK";
            } else {
                $data["message"] = "Error: The password you have entered is wrong.";
            }
            return $res->withJson($data, 200);
        } catch (\PDOException $e) {
            return $res->withJson(["error" => true, "message" => "DataBase Error: {$e->getMessage()}"], 400);
        } catch (\Exception $e) {
            return $res->withJson(["error" => true, "message" => "General Error: {$e->getMessage()}"], 400);
        } finally {
            $db->destroyConnection();
        }
    }

    public function listNodes(Bootstrap $app, Response $res, ServerRequest $req): Response
    {
        $id = $req->getParam("id", null);

        $db = $app->getMiddlewareService("db");
        $jwt = $app->getMiddlewareService("jwt");

        $sql = "SELECT * FROM SALEM3.dbo.Usuarios_Niveles_Relaciones WHERE 1=1 AND id_usuario = :user;";
        $params = [":user" => $id];

        try {
            $query = $db->query($sql, $params);
            $app->printScreenDebug($query);
            if (!$query) {
                $data["status"] = "Error: The user specified does not exist.";
                return $res->withJson($data,400);
            } 

            foreach ($query as $row) {
                $data[$row->tabla_nodo][] = $row->id_nodo;
            }
            $totalRegs = [];
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                $sql2 = "SELECT * FROM {$key} WHERE 1=1 AND id IN ({$value})";
                $query2 = $db->query($sql2);
                $totalRegs = array_merge($totalRegs, $query2);
            }
            $data = $totalRegs;
            return $res->withJson($data, 200);
        } catch (\PDOException $e) {
            return $res->withJson(["error" => true, "message" => "DataBase Error: {$e->getMessage()}"],400);
        } catch (\Exception $e) {
            return $res->withJson(["error" => true, "message" => "General Error: {$e->getMessage()}"],400);
        } finally {
            $db->destroyConnection();
        }
    }
}
