<?php



class ProductDB
{
    private PDO $_connection;
    private static ProductDB $_instance;
    private string $_host = 'dbpack-product';
    private int $_port = 13307;
    private string $_username = 'dksl';
    private string $_password = '123456';
    private string $_database = 'product';

    const allocateInventorySql = "update /*+ XID('%s') TraceParent('%s') */ product.inventory set available_qty = available_qty - ?, 
		allocated_qty = allocated_qty + ? where product_sysno = ? and available_qty >= ?";

    public static function getInstance(): ProductDB
    {
        if (empty(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct()
    {
        try {
            $this->_connection = new PDO(
                "mysql:host=$this->_host;port=$this->_port;dbname=$this->_database;charset=utf8",
                $this->_username,
                $this->_password,
                [
                    PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    private function __clone()
    {
    }

    public function getConnection(): PDO
    {
        return $this->_connection;
    }

    public function allocateInventory(string $xid, string $traceParent, array $inventories): bool
    {
        $this->getConnection()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        try {
            $this->getConnection()->beginTransaction();
            foreach ($inventories as $inventory) {
                $allocateInventorySql = sprintf(self::allocateInventorySql, $xid, $traceParent);

                $statement = $this->getConnection()->prepare($allocateInventorySql);
                $statement->bindValue(1, $inventory['Qty']);
                $statement->bindValue(2, $inventory['Qty']);
                $statement->bindValue(3, $inventory['ProductSysNo']);
                $statement->bindValue(4, $inventory['Qty']);

                $result = $statement->execute();
                if (!$result) {
                    throw new PDOException("failed to allocateInventory");
                }
            }
            $this->getConnection()->commit();
        } catch (PDOException $e) {
            $this->getConnection()->rollBack();
            return false;
        }
        return true;
    }
}

