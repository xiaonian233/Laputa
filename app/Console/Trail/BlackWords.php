<?php

/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2018/1/2
 * Time: 下午8:49
 */

namespace App\Console\Trail;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class BlackWords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'BlackWords';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'write black words';
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $resTrie = trie_filter_new();
        $tempKeyFile = 'tempKey.txt';

        $data = Redis::LRANGE('blackwords', 0, -1);

        $fp = fopen($tempKeyFile, 'w');
        if ( ! $fp)
        {
            return;
        }
        foreach ($data as $v)
        {
            fwrite($fp, "$v\r\n");
        }
        fclose($fp);

        $fp = fopen($tempKeyFile, 'r');
        if ( ! $fp)
        {
            return;
        }

        while ( ! feof($fp))
        {
            $word = fgets($fp, 1024);
            if ( ! empty($word))
            {
                trie_filter_store($resTrie, $word);
            }
        }

        trie_filter_save($resTrie, '/var/www/api/app/Services/Trial/' . 'blackword.tree');
        return;
    }
}