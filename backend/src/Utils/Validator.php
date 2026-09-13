<?php
/**
 * 输入验证工具类
 */

namespace App\Utils;

class Validator
{
    private array $errors = [];
    private array $data;
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    /**
     * 必填验证
     */
    public function required(string $field, ?string $label = null): self
    {
        $label = $label ?? $field;
        
        if (!isset($this->data[$field]) || trim((string)$this->data[$field]) === '') {
            $this->errors[$field] = "{$label}不能为空";
        }
        
        return $this;
    }
    
    /**
     * 最小长度验证
     */
    public function minLength(string $field, int $min, ?string $label = null): self
    {
        $label = $label ?? $field;
        
        if (isset($this->data[$field]) && mb_strlen((string)$this->data[$field]) < $min) {
            $this->errors[$field] = "{$label}长度不能少于{$min}个字符";
        }
        
        return $this;
    }
    
    /**
     * 最大长度验证
     */
    public function maxLength(string $field, int $max, ?string $label = null): self
    {
        $label = $label ?? $field;
        
        if (isset($this->data[$field]) && mb_strlen((string)$this->data[$field]) > $max) {
            $this->errors[$field] = "{$label}长度不能超过{$max}个字符";
        }
        
        return $this;
    }
    
    /**
     * 邮箱验证
     */
    public function email(string $field, ?string $label = null): self
    {
        $label = $label ?? $field;
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = "{$label}格式不正确";
            }
        }
        
        return $this;
    }
    
    /**
     * 日期验证
     */
    public function date(string $field, ?string $label = null): self
    {
        $label = $label ?? $field;
        
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $date = \DateTime::createFromFormat('Y-m-d', $this->data[$field]);
            if (!$date || $date->format('Y-m-d') !== $this->data[$field]) {
                $this->errors[$field] = "{$label}日期格式不正确";
            }
        }
        
        return $this;
    }
    
    /**
     * 数字验证
     */
    public function numeric(string $field, ?string $label = null): self
    {
        $label = $label ?? $field;
        
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = "{$label}必须是数字";
        }
        
        return $this;
    }
    
    /**
     * 获取验证结果
     */
    public function validate(): bool
    {
        return empty($this->errors);
    }
    
    /**
     * 获取错误信息
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * 获取第一个错误信息
     */
    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}
