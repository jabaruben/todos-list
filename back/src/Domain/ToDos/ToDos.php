<?php

namespace src\Domain;

use Error;
use TodosList\Infraestructure\Bootstrap;
use TodosList\Infraestructure\Database\Interfaces\Database;
use src\Infraestructure\HTTP\Response;
use src\Infraestructure\HTTP\ServerRequest;

class ToDos
{
    public function list(Bootstrap $app, Response $res, ServerRequest $req): Response
    {
        $db = $app->getMiddlewareService("db");
        
        $sql = "SELECT todos.id as todo_id, todos.todo, categories.id as category_id, categories.name FROM todos LEFT JOIN categories_todos ON todos.id = categories_todos.id_todo LEFT JOIN categories ON categories.id = categories_todos.id_category WHERE 1=1 ORDER BY todos.id ASC, categories.id ASC";
        $params = [];

        try {
            $query = $db->Select($sql, $params);
            $app->printScreenDebug($query);
            if (!$query) {
                $data["error"] = true;
                $data["message"] = "Error: No data to retrieve.";
                return $res->withJson($data, 404);
            }
            $data["error"] = false;
            $data["query"] = $this->prepareList($query);
            return $res->withJson($data, 200);
        } catch (\PDOException $e) {
            return $res->withJson(["error" => true, "message" => "DataBase Error: {$e->getMessage()}"], 404);
        } catch (\Exception $e) {
            return $res->withJson(["error" => true, "message" => "General Error: {$e->getMessage()}"], 404);
        } finally {
            // 
        }
    }

    public function add(Bootstrap $app, Response $res, ServerRequest $req): Response
    {
        $todoName = $req->getParam("todoname", null);
        $categories = $req->getParam("categories", null);
        
        $db = $app->getMiddlewareService("db");

        try {
            $todoId = $this->insertToDo($app, $db, $todoName);
            is_array($categories) && count($categories) > 0 && $this->insertCategoriesTodos($app, $db, $todoId, $categories);
            $data["error"] = false;
            return $res->withJson($data, 201);
        } catch (\PDOException $e) {
            return $res->withJson(["error" => true, "message" => "DataBase Error: {$e->getMessage()}"], 404);
        } catch (\Exception $e) {
            return $res->withJson(["error" => true, "message" => "General Error: {$e->getMessage()}"], 404);
        } finally {
            // 
        }
    }

    public function delete(Bootstrap $app, Response $res, ServerRequest $req): Response
    {
        $id = $req->getParam("id", null);

        $db = $app->getMiddlewareService("db");
        
        $sql = "DELETE FROM todos WHERE id = :id";
        $params = [":id" => $id];

        try {
            $query = $db->Remove($sql, $params);
            $data["error"] = false;
            return $res->withJson($data, 200);
        } catch (\PDOException $e) {
            return $res->withJson(["error" => true, "message" => "DataBase Error: {$e->getMessage()}"], 404);
        } catch (\Exception $e) {
            return $res->withJson(["error" => true, "message" => "General Error: {$e->getMessage()}"], 404);
        } finally {
            // 
        }
    }

    private function prepareList(array $data): array{
        $return = [];
        foreach ($data as $row) {
            $todoId = $row["todo_id"];
            $todoName = $row["todo"];
            $todoCatId = $row["category_id"];
            $todoCatName = $row["name"];

            if(!array_key_exists($todoId, $return)){
                $return[$todoId] = [
                    "todo_name" => $todoName,
                    "categories" => []
                ];
            }
            
            if(!in_array($todoCatId, [null, "", 0])){
                $return[$todoId]["categories"][$todoCatId] = $todoCatName;
            }
        }

        return $return;
    }

    private function insertToDo(Bootstrap $app, Database $db, string $todoName): string{
        $sql = "INSERT INTO todos(todo) VALUES (:todoName);";
        $params = [":todoName" => $todoName];

        try {
            $lastInsertId = $db->Insert($sql, $params);
            $app->printScreenDebug($lastInsertId);
            if ($lastInsertId === "") {
                throw new Error("Error de insercion");
            }
            return $lastInsertId;
        } catch (\PDOException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        } finally {
            // 
        }
    }

    private function insertCategoriesTodos(Bootstrap $app, Database $db, string $todoId, array $categories): string {
        $sql = "INSERT INTO categories_todos(id_todo, id_category) VALUES ";
        $arrPlaceholders = [];
        $params = [];
        foreach ($categories as $category) {
            $arrPlaceholders[] = "(?, ?)";
            $params[] = $todoId;
            $params[] = $category;
        }
        $sql .= implode(", ", $arrPlaceholders);
        try {
            $lastInsertId = $db->Insert($sql, $params);
            $app->printScreenDebug($lastInsertId);
            if ($lastInsertId === "") {
                throw new Error("Error de insercion");
            }
            return $lastInsertId;
        } catch (\PDOException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        } finally {
            // 
        }
    }
}