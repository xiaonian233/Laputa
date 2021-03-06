<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2018/7/9
 * Time: 下午2:33
 */

namespace App\Api\V1\Services\Counter\Base;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MigrateCounterService
{
    protected $table;
    protected $field;
    protected $timeout = 60;
    /**
     * 使用场景：访问统计，需要定期将数据回写数据，没有关联表
     */
    public function __construct($tableName, $filedName)
    {
        $this->table = $tableName;
        $this->field = $filedName;
    }

    public function add($id, $num = 1)
    {
        $this->id = $id;
        $cacheKey = $this->cacheKey($id);

        if (Redis::EXISTS($cacheKey))
        {
            $result = Redis::INCRBY($cacheKey, $num);
            $writeKey = $this->writeKey($id);

            if (
                !Redis::EXISTS($writeKey) ||
                time() - Redis::get($writeKey) > $this->timeout
            )
            {
                $this->set($id, $result);
            }

            return $result;
        }
        else
        {
            $count = $this->migration($id);
            $arr = [];
            $arr[$this->field] = $count + $num;

            DB::table($this->table)
                ->where('id', $id)
                ->update($arr);
        }

        return $this->get($id);
    }

    public function get($id)
    {
        $this->id = $id;
        $cacheKey = $this->cacheKey($id);

        if (Redis::EXISTS($cacheKey))
        {
            return Redis::get($cacheKey);
        }

        $count = $this->migration($id);

        $this->writeCache($cacheKey, $count);
        $this->writeCache($this->writeKey($id), time());

        return $count;
    }

    public function batchGet($list, $key)
    {
        foreach ($list as $i => $item)
        {
            $list[$i][$key] = (int)$this->get($item['id']);
        }

        return $list;
    }

    protected function set($id, $result)
    {
        DB::table($this->table)
            ->where('id', $id)
            ->update([
                $this->field => $result
            ]);

        $this->writeCache($this->writeKey($id), time());
    }

    protected function migration($id)
    {
        return DB::table($this->table)
            ->where('id', $id)
            ->pluck($this->field)
            ->first();
    }

    protected function writeCache($key, $value)
    {
        Redis::set($key, $value);
        Redis::EXPIRE($key, $this->timeout * 2);
    }

    protected function cacheKey($id)
    {
        return $this->table . '_' . $id . '_' . $this->field;
    }

    protected function writeKey($id)
    {
        return $this->table . '_' . $id . '_' . $this->field . '_' . 'last_add_at';
    }
}