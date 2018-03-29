<?php
/**
 * Created by PhpStorm.
 * User: amila
 * Date: 3/28/18
 * Time: 12:41 PM
 */

class Background extends Thread {

    public function __construct(callable $call, array $args = []) {
        $this->call = $call;
        $this->args = $args;
    }

    public function run() {
        call_user_func_array($this->call, $this->args);
    }

    protected $call;
    protected $args;
}