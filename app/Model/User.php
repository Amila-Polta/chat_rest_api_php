<?php

App::uses('AppModel', 'Model');

class User extends AppModel
{


    /**
     *  This is to validate user.
     *  This check for the required data to complete a user
     * @param $user user to validate
     * @return bool isValid
     */
    function validate($user) {

        //Check email
        if (isset($user->email)) {
            if (empty($user->email)) {
                return false;
            }
        } else {
            return false;
        }

        //Check password
        if (isset($user->password)) {
            if (empty($user->password)) {
                return false;
            }
        } else {
            return false;
        }

        //Check first_name
        if (isset($user->first_name)) {
            if (empty($user->first_name)) {
                return false;
            }
        } else {
            return false;
        }

        //Check last_name
        if (isset($user->last_name)) {
            if (empty($user->last_name)) {
                return false;
            }
        } else {
            return false;
        }

        //Check user_type
        if (isset($user->user_type)) {
            if (empty($user->user_type)) {
                return false;
            } else {
                if (strcmp($user->user_type, "Admin") === 0) {
                } else if (strcmp($user->user_type, "User") === 0){
                } else {
                    return false;
                }

            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * This is to find a user by his/her email
     * @param $email string email address to be searched
     * @return mixed user found in the db (might return null)
     */
    public function findUserByEmail ($email) {
        return $this->find('first', array(
            'conditions'=>array('email'=>$email)
        ));
    }

    /**
     * This is to hash the password
     * @param $password string to be hashed
     * @return string hashed password
     */
    public function hashUserPassword ($password) {
        return password_hash(
            $password, PASSWORD_DEFAULT
        );
    }

    /**
     * This is to compare password to find isEqual
     * @param $password string not hashed (from req)
     * @param $hashedPassword string hashed (from db)
     * @return bool isEqual
     */
    public function matchHashedPassword($password, $hashedPassword){
        return password_verify($password, $hashedPassword);
    }

    /**
     * This is to move data from req to updating user
     * @param $userFromDb user in local db
     * @param $userFromReq user data to be changed
     * @return mixed User object to be updated
     */
    public function transferUserDataToUpdate ($userFromDb, $userFromReq) {

        $userFromReq->id = $userFromDb['User']['id'];
        $userFromReq->auth_token = $userFromDb['User']['auth_token'];

        //Have first name to update
        if (isset($userFromReq->first_name)){
            if (empty($userFromReq->first_name)){
                $userFromReq->first_name = $userFromDb['User']['first_name'];
            }
        } else {
            $userFromReq->first_name = $userFromDb['User']['first_name'];
        }

        //Have last name to update
        if (isset($userFromReq->last_name)){
            if (empty($userFromReq->last_name)){
                $userFromReq->last_name = $userFromDb['User']['last_name'];
            }
        } else {
            $userFromReq->last_name = $userFromDb['User']['last_name'];
        }

        //Have password to update
        if (isset($userFromReq->password)){
            if (empty($userFromReq->password)){
                $userFromReq->password = $userFromDb['User']['password'];
            } else {
                $userFromReq->password = $this->hashUserPassword($userFromReq->password);
            }
        } else {
            $userFromReq->password = $userFromDb['User']['password'];
        }

        //Have image urlto update
        if (isset($userFromReq->image_url)){
            if (empty($userFromReq->image_url)){
                $userFromReq->image_url = $userFromDb['User']['image_url'];
            }
        } else {
            $userFromReq->image_url = $userFromDb['User']['image_url'];
        }

        //Have push token to update
        if (isset($userFromReq->push_token)){
            if (empty($userFromReq->push_token)){
                $userFromReq->push_token = $userFromDb['User']['push_token'];
            }
        } else {
            $userFromReq->push_token = $userFromDb['User']['push_token'];
        }

        return $userFromReq;
    }


    public function findUserByAuthToken ($token) {
        return $this->find('first', array(
            'conditions'=>array('auth_token'=>$token)
        ));
    }

    public function findUsersFromIds ($ids) {
        return $this->find('all',
            array('conditions' => array(
                'id' => $ids
            )));
    }

}