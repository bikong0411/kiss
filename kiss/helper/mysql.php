<?php
namespace kiss\helper;

class MySql
{

    /**
     * 构造
     * @param array $db_conf arry(host, port, dbname, usr, pwd)
     * @param boolean $persistent or not
     * @throws Exception
     */
    protected function __construct(array $db_conf, $persistent = true)
    {
        try
        {
            $dsn = "mysql:host={$db_conf['host']};port={$db_conf['port']};dbname={$db_conf['db']}";
            $this->_pdo = new \PDO($dsn, $db_conf['usr'], $db_conf['pwd'], array(
                        \PDO::ATTR_PERSISTENT => $persistent,
                        \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    ));
        }
        catch (\Exception $ex)
        {
            $this->_error = $ex;
            return false;
        }
    }

    /**
     * 获取 MySQL PDO 实例
     * @param array $db_conf arry(host, port, dbname, usr, pwd)
     * @param boolean $persistent or not
     * @throws Exception
     */
    public static function getInstance($db_conf, $persistent = true)
    {
        static $instance;
        $db_index = "{$db_conf['host']}:{$db_conf['port']}";
        !isset($instance[$db_index]) && $instance[$db_index] = new self($db_conf, $persistent);
        return $instance[$db_index];
    }

    /**
     *
     * @param string $sql
     * @return array|false
     */
    public function query($sql)
    {
        try
        {
            $this->_pdoStatement = $this->_pdo->prepare($sql);
            $this->_pdoStatement->setFetchMode(\PDO::FETCH_ASSOC);
            $this->_pdoStatement->execute();
            return $this->_pdoStatement->fetchAll();
        }
        catch (\Exception $ex)
        {
            $this->_error[] = array($ex->getCode(), $ex->getMessage(), $sql);
            return false;
        }
    }

    /**
     *
     * @param string $sql
     * @return boolean
     */
    public function execute($sql)
    {
        try
        {
            return $this->_pdo->exec($sql);
        }
        catch (\Exception $ex)
        {
            file_put_contents(__DIR__ . "/log/mysql_error/" . date("Ymd"), $ex->getCode() . "\t" . $ex->getMessage() . "\t" . $sql . "\n", FILE_APPEND);
            $this->_error[] = array($ex->getCode(), $ex->getMessage(), $sql);
            return false;
        }
    }

    /**
     * 开始一个事务
     * eg: ->beginTransaction(); ->execute(); ->execute(); ->commit();
     */
    public function beginTransaction()
    {
        $this->_pdo->beginTransaction();
    }

    /**
     * 提交事务
     * @return boolean
     */
    public function commit()
    {
        if ($this->_error)
        {
            $this->_pdo->rollback();
            return false;
        }
        else
        {
            $this->_pdo->commit();
            return true;
        }
    }

    /**
     * get mysql error
     * @return array(mysql_errno, mysql_error)
     */
    public function error()
    {
        return $this->_error;
    }

}