<?php

namespace App\Exceptions;

use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Throwable;

class DatabaseQueryException extends QueryException
{
    /**
     * Create a new query exception instance.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @param  \Throwable  $previous
     * @return void
     */
    public function __construct(QueryException $exception)
    {
        if ($exception instanceof QueryException) {
            $this->errorInfo = $exception->errorInfo;
        } else {
            throw $exception;
        }

        parent::__construct($exception->getSql(), $exception->getBindings(), $exception);
    }

    /**
     * Format the SQL error message.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @param  \Throwable  $previous
     * @return string
     */
    protected function formatMessage($sql, $bindings, Throwable $previous)
    {
        $bindings = array_map(function ($item) {
            return is_numeric($item) ? $item : "'$item'";
        }, $bindings);

        $message = $previous->getMessage();

        return substr($message, 0, strpos($message, ' (SQL: ')) . ' (SQL: '.Str::replaceArray('?', $bindings, $sql).')';
    }
}
