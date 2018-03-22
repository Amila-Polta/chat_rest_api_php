<?php

class Authentication {



    public function authenticateUser($headers) {

        //get auth token from headers
        $authToken = $headers['X-AUTH-TOKEN'];

        if (empty($authToken)){
            return null;
        }

        $userModel = new User();

        $user = $userModel->findUserByAuthToken($authToken);

        var_dump($user);

    }

}