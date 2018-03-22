<?php

App::import('vendor', 'Firebase') ;
App::uses('Geshi', 'Vendor');

require '../../../vendors/autoload.php';
use Kreait\Firebase\Configuration;
use Kreait\Firebase\Firebase;
use Kreait\Firebase\Query;


class JWTokenComponent {

    public $helpers = array('\Firebase\JWT\JWT');

    const DEFAULT_URL = 'https://practera-notification.firebaseio.com/';
    const DEFAULT_TOKEN = '595d807c94af57bacdfa412c5ee94cefc598905d';
    const DEFAULT_PATH = '/practera-notification';

    public function createUserJWToken($email, $id){
        $firebase = new \Firebase\FirebaseLib(DEFAULT_URL, DEFAULT_TOKEN);

// --- storing an array ---
        $test = array(
            "foo" => "bar",
            "i_love" => "lamp",
            "id" => 42
        );
        $dateTime = new DateTime();
        $firebase->set(DEFAULT_PATH . '/' . $dateTime->format('c'), $test);

// --- storing a string ---
        $firebase->set(DEFAULT_PATH . '/name/contact001', "John Doe");

// --- reading the stored string ---
        $name = $firebase->get(DEFAULT_PATH . '/name/contact001');

        configureFireBase();
    }

    function configureFireBase()
    {
        $config = new Configuration();
        $config->setAuthConfigFile(__DIR__ . '/firebase-7055663f53c4.json');
        $firebase = new Firebase('https://upwork-test.firebaseio.com/', $config);
        return $firebase;
    }


    public function createAuthJWToken($user){
        $key = "chat_app_auth_key";
        return JWT::encode($user, $key);
    }

}