<?php
/**
 * Created by PhpStorm.
 * User: z
 * Date: 18-8-22
 * Time: 上午6:08
 */

class WSServer
{
    private $redisSubPid;

    private $wsServerPid;

    private $mysqlConnection;

    public function __construct()
    {
        $this->currentEnvIsSupport();
        $this->mysqlConnection('127.0.0.1', 'root');
    }

    public function start()
    {
        $subPid = pcntl_wait($status);
        while (true) {
            if ($this->wsServerPid == $subPid) {
                $this->wsServer();
                $this-start();
            }
            if ($this-redisSubPid == $subPid) {
                $this->redisSubscribeMsg();
                $this-start();
            }
        }
    }
    private function currentEnvIsSupport()
    {
        $result = [
            'status' => 'success',
            'msg' => '',
        ];
        foreach (['sockets', 'redis', 'pcntl'] as $extension) {
            if (!extension_loaded($extension)) {
                $result['status'] = 'failed';
                $result['msg'] = '请先安装 php ' . $extension . '扩展';
                break;
            }
        }
        if (!$result['status'] === 'success') {
            exit($result['msg']);
        }
    }

    private function redisSubscribeMsg(array $chans, string $host = '127.0.0.1', int $port = 6379, string $redisPassword = '')
    {
        $redisSubPid = pcntl_fork();
        if ($redisSubPid) {
            $this->redisSubPid = $redisSubPid;
        }
        if ($redisSubPid == 0) {
            ini_set('default_socket_timeout', -1);
            $redis = new \redis;
            $redis->pconnect($host, $port);
            if ($redisPassword) {
                $redis->auth($redisPassword);
            }
            $redis->subscribe($chans, function ($redis, $chan, $msg) {
                switch ($chan) {
                    case 'first_chan':
                        break;
                    case 'second_chan':
                        break;
                }

            });
            $this->mysqlConnection->query();
        }
    }

    private function mysqlConnection($host, $username, $password, $dbName, $port)
    {
        $mysqlConnection = new mysqli($host, $username, $password, $dbName, $port);
        $mysqlConnection->set_charset('utf8');
        $this->mysqlConnection = $mysqlConnection;
    }

    private function wsServer()
    {
        $wsServerPid = pcntl_fork();
        if ($wsServerPid) {
            $this->wsServerPid = $wsServerPid;
        }
        if ($wsServerPid == 0) {
            error_reporting(E_ALL);
            set_time_limit(0);
            date_default_timezone_set('Asia/Shanghai');
            ini_set('memory_limit', '2048m');
            new WebSocket("0.0.0.0", "8081");
        }
    }
}