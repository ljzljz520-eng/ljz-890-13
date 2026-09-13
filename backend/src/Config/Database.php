<?php
/**
 * 数据库连接配置
 */

namespace App\Config;

use PDO;
use PDOException;
use App\Utils\Logger;

class Database
{
    private static ?PDO $instance = null;
    
    private static string $host;
    private static string $port;
    private static string $database;
    private static string $username;
    private static string $password;
    
    /**
     * 获取数据库连接实例
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::loadConfig();
            self::connect();
        }
        
        return self::$instance;
    }
    
    /**
     * 加载配置
     */
    private static function loadConfig(): void
    {
        self::$host = getenv('DB_HOST') ?: 'db';
        self::$port = getenv('DB_PORT') ?: '3306';
        self::$database = getenv('DB_DATABASE') ?: 'memorial';
        self::$username = getenv('DB_USERNAME') ?: 'root';
        self::$password = getenv('DB_PASSWORD') ?: 'root';
    }
    
    /**
     * 建立连接
     */
    private static function connect(): void
    {
        $logger = Logger::getInstance();
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            self::$host,
            self::$port,
            self::$database
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        $maxRetries = 30;
        $retryDelay = 2;
        
        for ($i = 1; $i <= $maxRetries; $i++) {
            try {
                self::$instance = new PDO($dsn, self::$username, self::$password, $options);
                $logger->info("Database connected successfully");
                return;
            } catch (PDOException $e) {
                $logger->warning("Database connection attempt {$i}/{$maxRetries} failed: " . $e->getMessage());
                
                if ($i === $maxRetries) {
                    $logger->error("Database connection failed after {$maxRetries} attempts");
                    throw new PDOException("无法连接数据库，请稍后重试");
                }
                
                sleep($retryDelay);
            }
        }
    }
    
    /**
     * 开始事务
     */
    public static function beginTransaction(): void
    {
        self::getInstance()->beginTransaction();
    }
    
    /**
     * 提交事务
     */
    public static function commit(): void
    {
        self::getInstance()->commit();
    }
    
    /**
     * 回滚事务
     */
    public static function rollback(): void
    {
        self::getInstance()->rollBack();
    }
}
