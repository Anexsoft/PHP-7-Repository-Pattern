<?php
namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Kodoti\Database\DbProvider;
use PDOException;

class OrderService
{
    private $_db;

    public function __construct()
    {
        $this->_db = DbProvider::get();
    }

    public function get(int $id): ?Order
    {
        $result = null;

        try {
            $stm = $this->_db->prepare('select * from orders where id = :id');
            $stm->execute(['id' => $id]);

            $data = $stm->fetchObject('\\App\\Models\\Order');

            if ($data) {
                $result = $data;

                // Client
                $result->client = $this->getUser($result->user_id);

                // Creater
                $result->creater = $this->getUser($result->creater_id);

                // Detail
                $result->detail = $this->getDetail($result->id);
            }
        } catch (PDOException $ex) {
            var_dump($ex);
        }

        return $result;
    }

    private function getDetail(int $order_id): array
    {
        $stm = $this->_db->prepare('select * from order_detail where order_id = :order_id');
        $stm->execute(['order_id' => $order_id]);

        $result = $stm->fetchAll(\PDO::FETCH_CLASS, '\\App\\Models\\OrderDetail');

        foreach ($result as $item) {
            $stm = $this->_db->prepare('select * from products where id = :product_id');
            $stm->execute(['product_id' => $item->product_id]);

            $item->product = $stm->fetchObject('\\App\\Models\\Product');
        }

        return $result;
    }

    private function getUser(int $id): User
    {
        $stm = $this->_db->prepare('select * from users where id = :id');
        $stm->execute(['id' => $id]);

        $result = $stm->fetchObject('\\App\\Models\\User');
        unset($result->password);

        return $result;
    }

    public function create(Order $model): void
    {
        try {
            // Begin transacation
            $this->_db->beginTransaction();

            // Prepare order creation
            $this->prepareOrderCreation($model);

            // Order creation
            $this->orderCreate($model);

            // Order Detail creation
            $this->orderDetailCreate($model);

            // Commit transaction
            $this->_db->commit();
        } catch (PDOException $ex) {
            $this->_db->rollBack();
        }
    }

    private function prepareOrderCreation(Order &$model): void
    {
        $now = date('Y-m-d H:i:s');

        $model->created_at = $now;
        $model->updated_at = $now;

        foreach ($model->detail as $item) {
            $item->total = $item->price * $item->quantity;

            $item->created_at = $now;
            $item->updated_at = $now;

            $model->total += $item->total;
        }
    }

    private function orderCreate(Order &$model): void
    {
        $stm = $this->_db->prepare('
            insert into orders(user_id, total, creater_id, created_at, updated_at)
            values(:user_id, :total, :creater_id, :created, :updated)
        ');

        $stm->execute([
            'user_id' => $model->user_id,
            'total' => $model->total,
            'creater_id' => $model->creater_id,
            'created' => $model->created_at,
            'updated' => $model->updated_at,
        ]);

        $model->id = $this->_db->lastInsertId();
    }

    private function orderDetailCreate(Order $model): void
    {
        foreach ($model->detail as $item) {
            $stm = $this->_db->prepare('
                insert into order_detail(order_id, product_id, quantity, price, total, created_at, updated_at)
                values(:order_id, :product_id, :quantity, :price, :total, :created_at, :updated_at)
            ');

            $stm->execute([
                'order_id' => $model->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total' => $item->total,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ]);
        }
    }
}
