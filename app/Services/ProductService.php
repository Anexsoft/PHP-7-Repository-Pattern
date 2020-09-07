<?php
namespace App\Services;

use App\Models\Product;
use Kodoti\Database\DbProvider;
use PDO;
use PDOException;

class ProductService
{
    private $_db;

    public function __construct()
    {
        $this->_db = DbProvider::get();
    }

    public function getAll(): array
    {
        $result = [];

        try {
            // 01. Prepare query
            $stm = $this->_db->prepare('select * from products');

            // 02. Execute query
            $stm->execute();

            // 03. Fetch All
            $result = $stm->fetchAll(PDO::FETCH_CLASS, '\\App\\Models\\Product');
        } catch (PDOException $ex) {

        }

        return $result;
    }

    public function get(int $id): ?Product
    {
        $result = null;

        try {
            $stm = $this->_db->prepare('select * from products where id = :id');
            $stm->execute(['id' => $id]);

            $data = $stm->fetchObject('\\App\\Models\\Product');

            if ($data) {
                $result = $data;
            }
        } catch (PDOException $ex) {

        }

        return $result;
    }

    public function create(Product $model): void
    {
        try {
            $stm = $this->_db->prepare(
                'insert into products(name, price, created_at, updated_at) values (:name, :price, :created, :updated)'
            );

            $now = date('Y-m-d H:i:s');

            $stm->execute([
                'name' => $model->name,
                'price' => $model->price,
                'created' => $now,
                'updated' => $now,
            ]);
        } catch (PDOException $ex) {

        }
    }

    public function update(Product $model): void
    {
        try {
            $stm = $this->_db->prepare('
                update products
                set name = :name,
                    price = :price,
                    updated_at = :updated
                where id = :id
            ');

            $stm->execute([
                'name' => $model->name,
                'price' => $model->price,
                'updated' => date('Y-m-d H:i:s'),
                'id' => $model->id,
            ]);
        } catch (PDOException $ex) {
            print $ex->getMessage();
        }
    }

    public function delete(int $id): void
    {
        try {
            $stm = $this->_db->prepare(
                'delete from products where id = :id'
            );

            $stm->execute(['id' => $id]);
        } catch (PDOException $ex) {

        }
    }
}
