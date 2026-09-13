<?php
/**
 * API响应工具类
 */

namespace App\Utils;

class Response
{
    /**
     * 成功响应
     */
    public static function success($data = null, string $message = '操作成功'): array
    {
        return [
            'code' => 200,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ];
    }
    
    /**
     * 错误响应
     */
    public static function error(string $message = '操作失败', int $code = 400): array
    {
        return [
            'code' => $code,
            'message' => $message,
            'data' => null,
            'timestamp' => time()
        ];
    }
    
    /**
     * 分页响应
     */
    public static function paginate(array $items, int $total, int $page, int $pageSize): array
    {
        return [
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize,
                'totalPages' => ceil($total / $pageSize)
            ],
            'timestamp' => time()
        ];
    }
}
