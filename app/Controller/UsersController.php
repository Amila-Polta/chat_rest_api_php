<?php

App::uses('AppController', 'Controller');
App::uses('User', 'Model');
App::uses('FirebaseComponent', 'Controller/Component');
App::uses('SendPushNotification', 'Controller/Component');
App::uses('JWTokenComponent', 'Controller/Component');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, X-AUTH-TOKEN");
class UsersController extends AppController
{


    /**
     * This is to authenticate user from token
     * @param $headers list of headers
     * @return user in db if found
     */
    public function authenticateUser($headers) {

        //get auth token from headers
        try{
            $authToken = $headers['X-AUTH-TOKEN'];
        } catch (Exception $e){
            return null;
        }

        if (empty($authToken)){
            return null;
        }

        $authToken = str_replace('\/', "/", $authToken);

        //Get user from auth token
        $user = $this->User->findUserByAuthToken($authToken);

        if (empty($user)){
            return null;
        }

        return $user;
    }

    /**
     * This is to add a user to the local database.
     *
     * @return string
     */
    public function add() {
        $this->response->type('json');
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $user = $this->request->input('json_decode');
            $email = $user->email;
            if ( !$this->User->validate($user)) {
                $this->response->statusCode(400);
                return json_encode(
                    array(
                        'message' => "Check parameters",
                        'data' => null));
            }

            $user_db = $this->User->findUserByEmail($email);

            if (empty($user_db)) {
                //Hashing the password
                $user->password = $this->User->hashUserPassword($user->password);

                $this->User->create();
                if ($this->User->save($user)) {
                    $user->password = "";
                    return json_encode(
                        array(
                            'message' => "User added",
                            'data' => $user));
                } else {
                    $this->response->statusCode(500);
                    return json_encode(
                        array(
                            'message' => "User was not added",
                            'data' => null));
                }
            } else {
                $this->response->statusCode(400);
                return json_encode(
                    array(
                        'message' => "User already exist",
                        'data' => null));
            }
        } else {
            $this->response->statusCode(400);
            return json_encode(
                array(
                    'message' => "Error in request",
                    'data' => null));
        }
    }


    /**
     * @param null $id user id to be updated
     * @return string
     */
    public function edit() {

        $this->response->type('json');
        $this->autoRender = false;

        //Authenticate request
        $loggedInUser = $this->authenticateUser(apache_request_headers());

        if (empty($loggedInUser)) {
            $this->response->statusCode(401);
            return json_encode(
                array(
                    'message' => 'User is not authenticated',
                    'data' => null
                )
            );
        }

        //Get user to update
        $update = $this->request->input('json_decode');

        $user = $this->User->transferUserDataToUpdate($loggedInUser, $update);


        if ($this->request->is(array('user', 'post'))) {
            if ($this->User->save($user)) {
                //User updated response
                return json_encode(
                    array(
                        'message' => "User has been updated",
                        'data' => $user
                    ));
            } else {
                //Error in update user response
                $this->response->statusCode(500);
                return json_encode(
                    array(
                        'message' => "User has not updated",
                        'data' => null
                    ));
            }
        } else {
            $this->response->statusCode(400);
            return json_encode(
                array(
                    'message' => "Error in request",
                    'data' => null
                ));
        }
    }

    /**
     * THis is to log user in
     * @return string User login details
     */
    public function login () {

        $this->response->type('json');
        $this->autoRender = false;

        //Get login details
        $loginDetails = $this->request->input('json_decode');

        //Check for email and password
        if (isset($loginDetails->email) && isset($loginDetails->password)) {
            if (empty($loginDetails->email) || empty($loginDetails->password)) {
                $this->response->statusCode(400);
                return json_encode(
                    array(
                        'message' => "Check the parameters",
                        'data' => null
                    )
                );
            }
        } else {
            $this->response->statusCode(400);
            return json_encode(
                array(
                    'message' => "Check the parameters",
                    'data' => null
                )
            );
        }

        //Find user by email
        $user = $this->User->findUserByEmail($loginDetails->email);
        if (empty($user)) {
            $this->response->statusCode(404);
            return json_encode(
                array(
                    'message' => "User not found",
                    'data' => null
                )
            );
        }

        $passwordReq = $loginDetails->password;
        $passwordDb = $user['User']['password'];
        $user = $this->User->transferUserDataToUpdate($user, $loginDetails);


        //Match with password
        $isEqual = $this->User->matchHashedPassword($passwordReq, $passwordDb);
        if (!$isEqual) {
            $this->response->statusCode(404);
            return json_encode(
                array(
                    'message' => "User not found",
                    'data' => null
                )
            );
        }

        //create token
        $tokenData = $user->email.$user->id.time();
        $token = $this->User->hashUserPassword($tokenData);
        if (empty($token)){
            $this->response->statusCode(500);
            return json_encode(
                array(
                    'message' => "Error while creating token",
                    'data' => null
                )
            );
        }

        //Update user
        $user->auth_token = $token;
        if (!$this->User->save($user)) {
            //Error in update user response
            $this->response->statusCode(500);
            return json_encode(
                array(
                    'message' => "User was not updated",
                    'data' => null
                ));
        }

        $user = $this->User->findUserByAuthToken($token);

        //send the token with token
        return json_encode(
            array(
                'message' => "User is logged in",
                'data' => $user['User']
            )
        );
    }

    /**
     * This is to list all Users
     * @return string
     */
    public function index() {
        $this->response->type('json');
        $this->autoRender = false;

        $loggedInUser = $this->authenticateUser(apache_request_headers());

        if (empty($loggedInUser)) {
            $this->response->statusCode(401);
            return json_encode(
                array(
                    'message' => 'User is not authenticated',
                    'data' => null
                )
            );
        }

        $users = $this->User->find('all',['fields' => ['id','first_name','last_name','email','image_url', 'user_type'],
            'order'=>array('first_name ASC', 'last_name ASC'),
            'conditions'=>array('id !='=>$loggedInUser['User']['id'], 'user_type !=' => 'Admin')]);

        function getUsers($users){
            return $users['User'];
        }

        $userList = array_map('getUsers', $users);

        return json_encode(
            array(
                'message' => 'User list',
                'data' => $userList
            )
        );
    }


    public function createOneToOneChat (){
        $this->response->type('json');
        $this->autoRender = false;

        //Authenticate
        $loggedInUser = $this->authenticateUser(apache_request_headers());
        if (empty($loggedInUser)) {
            $this->response->statusCode(401);
            return json_encode(
                array(
                    'message' => 'User is not authenticated',
                    'data' => null
                )
            );
        }

        //Get data from req body
        $requestData = $this->request->input('json_decode');
        if (empty($requestData)) {
            $this->response->statusCode(400);
            return json_encode(
                array(
                    'message' => 'Request must have data',
                    'data' => null
                )
            );
        }

        //Find user
        $user = $this->User->findById($requestData->user_id);
        if (!$user) {
            $this->response->statusCode(404);
            return json_encode(
                array(
                    'message' => 'User was not found',
                    'data' => null
                )
            );
        }

        //Create User
        $fc = new FirebaseComponent();
        return ($fc->createOneToOneChat($loggedInUser, $user));
    }


    /**
     * This is to create messages groups
     * @return mixed|string
     */
    public function createGroupChat () {
        $this->response->type('json');
        $this->autoRender = false;

        //Authenticate
        $loggedInUser = $this->authenticateUser(apache_request_headers());
        if (empty($loggedInUser)) {
            $this->response->statusCode(401);
            return json_encode(
                array(
                    'message' => 'User is not authenticated',
                    'data' => null
                )
            );
        }

        //Get data from req body
        $requestData = $this->request->input('json_decode',true);
        if (empty($requestData)) {
            $this->response->statusCode(400);
            return json_encode(
                array(
                    'message' => 'Request must have data',
                    'data' => null
                )
            );
        }

        if (empty($requestData['members'])) {
            $this->response->statusCode(400);
            return json_encode(
                array(
                    'message' => 'Request must have data',
                    'data' => null
                )
            );
        }

        $adminIds = array_keys($requestData['members'], "admin", false);
        $userIds = array_keys($requestData['members'], "member", false);

        foreach ($adminIds as $adminId) {
            array_push($userIds, $adminId);
        }

        $key = array_search($loggedInUser['User']['id'], $userIds);
        unset($userIds[$key]);

        $userList = $this->User->findUsersFromIds($userIds);


        function getUsersPushTokens($userList){
            if (!empty($userList['User']['push_token'])) {
                return $userList['User']['push_token'];
            }
        }

        $pushTokens = array_map('getUsersPushTokens', $userList);


        $fc = new FirebaseComponent();
        return $fc->createGroupThreads($requestData, $pushTokens);
    }


    public function sendMessage () {

        $this->response->type('json');
        $this->autoRender = false;

        //Authenticate
        $loggedInUser = $this->authenticateUser(apache_request_headers());
        if (empty($loggedInUser)) {
            $this->response->statusCode(401);
            return json_encode(
                array(
                    'message' => 'User is not authenticated',
                    'data' => null
                )
            );
        }

        //Get request data
        $requestData = $this->request->input('json_decode');
        if (empty($requestData)) {
            $this->response->statusCode(400);
            return json_encode(
                array(
                    'message' => 'Request must have data',
                    'data' => null
                )
            );
        }

        $fc = new FirebaseComponent();

        if ($requestData->message_type === 'Group') {
            $recipient = $fc->getOtherUsersInThread($requestData->message_type, $requestData->thread_id, $loggedInUser);
            if (empty($recipient)){
                $this->response->statusCode(400);
                return json_encode(
                    array(
                        'message' => 'This user is not in group',
                        'data' => null
                    )
                );
            }
            $requestData->recipient = $recipient;
        }

        $response = $fc->sendMessage($loggedInUser, $requestData);

//        $background = new Background('sendPushNotification', [$requestData, $loggedInUser]);
//        $background->start();
//        $background->join();

        $this->sendPushNotification($requestData, $loggedInUser);

        return $response;
    }


    public function getSelf(){

        $this->response->type('json');
        $this->autoRender = false;

        //Authenticate
        $loggedInUser = $this->authenticateUser(apache_request_headers());
        if (empty($loggedInUser)) {
            $this->response->statusCode(401);
            return json_encode(
                array(
                    'message' => 'User is not authenticated',
                    'data' => null
                )
            );
        }

        return json_encode(
            array(
                'message' => 'Your profile',
                'data' => $loggedInUser['User']
            )
        );

    }


    public function logout(){
        $this->response->type('json');
        $this->autoRender = false;

        //Authenticate
        $loggedInUser = $this->authenticateUser(apache_request_headers());
        if (empty($loggedInUser)) {
            $this->response->statusCode(401);
            return json_encode(
                array(
                    'message' => 'User is not authenticated',
                    'data' => null
                )
            );
        }


        //Update User
        $user = $loggedInUser['User'];
        $user['auth_token'] = "" ;
        $user['push_token'] = "" ;
        if (!$this->User->save($user)) {
            //Error in update user response
            $this->response->statusCode(500);
            return json_encode(
                array(
                    'message' => "User was not updated",
                    'data' => null
                ));
        }

        return json_encode(
            array(
                'message' => "User logged out",
                'data' => null
            ));

    }


    public function sendPushNotification($requestData, $loggedInUser){
        $spn = new SendPushNotification();

        $userList = $this->User->findUsersFromIds($requestData->recipient);


        function getUsersPushTokens($userList){
            if (!empty($userList['User']['push_token'])) {
                return $userList['User']['push_token'];
            }
        }

        $pushTokens = array_map('getUsersPushTokens', $userList);


        if ($requestData->message_type === 'Private'){
            $spn->sendPushNotificationPrivate($requestData, $loggedInUser, $pushTokens);
        } else if ($requestData->message_type === 'Group'){
            $spn->sendPushNotificationGroup($requestData, $pushTokens);
        }

    }

    public function leaveGroup(){
        $this->response->type('json');
        $this->autoRender = false;

        //Authenticate
        $loggedInUser = $this->authenticateUser(apache_request_headers());
        if (empty($loggedInUser)) {
            $this->response->statusCode(401);
            return json_encode(
                array(
                    'message' => 'User is not authenticated',
                    'data' => null
                )
            );
        }

        //Get request data
        $requestData = $this->request->input('json_decode');
        if (empty($requestData)) {
            $this->response->statusCode(400);
            return json_encode(
                array(
                    'message' => 'Request must have data',
                    'data' => null
                )
            );
        }

        $fc = new FirebaseComponent();
        return $fc->removeUserFromGroup($loggedInUser['User']['id'], $requestData);
    }


    public function removeUserFromGroup(){
        $this->response->type('json');
        $this->autoRender = false;

        //Authenticate
        $loggedInUser = $this->authenticateUser(apache_request_headers());
        if (empty($loggedInUser)) {
            $this->response->statusCode(401);
            return json_encode(
                array(
                    'message' => 'User is not authenticated',
                    'data' => null
                )
            );
        }

        //Get request data
        $requestData = $this->request->input('json_decode');
        if (empty($requestData)) {
            $this->response->statusCode(400);
            return json_encode(
                array(
                    'message' => 'Request must have data',
                    'data' => null
                )
            );
        }

        $fc = new FirebaseComponent();
        return $fc->removeUserFromGroup($requestData->removed_user_id, $requestData);
    }


    public function editGroup(){
        $this->response->type('json');
        $this->autoRender = false;

        //Authenticate
        $loggedInUser = $this->authenticateUser(apache_request_headers());
        if (empty($loggedInUser)) {
            $this->response->statusCode(401);
            return json_encode(
                array(
                    'message' => 'User is not authenticated',
                    'data' => null
                )
            );
        }

        //Get request data
        $requestData = $this->request->input('json_decode');
        if (empty($requestData)) {
            $this->response->statusCode(400);
            return json_encode(
                array(
                    'message' => 'Request must have data',
                    'data' => null
                )
            );
        }

        $fc = new FirebaseComponent();

        return $fc->editGroup($requestData);
    }

    public function addUserToGroup (){
        $this->response->type('json');
        $this->autoRender = false;

        //Authenticate
        $loggedInUser = $this->authenticateUser(apache_request_headers());
        if (empty($loggedInUser)) {
            $this->response->statusCode(401);
            return json_encode(
                array(
                    'message' => 'User is not authenticated',
                    'data' => null
                )
            );
        }

        //Get request data
        $requestData = $this->request->input('json_decode');
        if (empty($requestData)) {
            $this->response->statusCode(400);
            return json_encode(
                array(
                    'message' => 'Request must have data',
                    'data' => null
                )
            );
        }

        $fc = new FirebaseComponent();

        return $fc->addPeopleToGroup($requestData);

    }

    public function listUserByIds() {
        $this->response->type('json');
        $this->autoRender = false;

        //Authenticate
        $loggedInUser = $this->authenticateUser(apache_request_headers());
        if (empty($loggedInUser)) {
            $this->response->statusCode(401);
            return json_encode(
                array(
                    'message' => 'User is not authenticated',
                    'data' => null
                )
            );
        }

        //Get request data
        $requestData = $this->request->input('json_decode');
        if (empty($requestData)) {
            $this->response->statusCode(400);
            return json_encode(
                array(
                    'message' => 'Request must have data',
                    'data' => null
                )
            );
        }

        $users = $this->User->findUsersFromIds($requestData->ids);

        function getUsers($users){
            return $users['User'];
        }

        $userList = array_map('getUsers', $users);

        return json_encode(
            array(
                'message' => 'User list',
                'data' => $userList
            )
        );
    }

    public function createHelpDeskChat () {
        $this->response->type('json');
        $this->autoRender = false;

        //Authenticate
        $loggedInUser = $this->authenticateUser(apache_request_headers());
        if (empty($loggedInUser)) {
            $this->response->statusCode(401);
            return json_encode(
                array(
                    'message' => 'User is not authenticated',
                    'data' => null
                )
            );
        }

        //Get request data
        $requestData = $this->request->input('json_decode');
        if (empty($requestData)) {
            $this->response->statusCode(400);
            return json_encode(
                array(
                    'message' => 'Request must have data',
                    'data' => null
                )
            );
        }

        $fc = new FirebaseComponent();

        return $fc->createHelpDeskChat($loggedInUser);

    }

    public function sendMessageHelpDesk () {
        $this->response->type('json');
        $this->autoRender = false;

        //Authenticate
        $loggedInUser = $this->authenticateUser(apache_request_headers());
        if (empty($loggedInUser)) {
            $this->response->statusCode(401);
            return json_encode(
                array(
                    'message' => 'User is not authenticated',
                    'data' => null
                )
            );
        }

        //Get request data
        $requestData = $this->request->input('json_decode');
        if (empty($requestData)) {
            $this->response->statusCode(400);
            return json_encode(
                array(
                    'message' => 'Request must have data',
                    'data' => null
                )
            );
        }

        $pushTokens = Array();
        if ($loggedInUser['User']['id'] === $requestData->thread_id){
            $admins = $this->User->getAdmins();

            function getUsersPushTokens($userList){
                if (!empty($userList['User']['push_token'])) {
                    return $userList['User']['push_token'];
                }
            }

            $pushTokens = array_map('getUsersPushTokens', $admins);

        } else {

            $user = $this->User->findById($requestData->thread_id);
            array_push($pushTokens, $user['User']['push_token']);
        }


        $fc = new FirebaseComponent();

        return $fc->sendHelpDeskMessage($requestData, $loggedInUser, $pushTokens);
    }

    public function getListUserNotInGroup () {
        $this->response->type('json');
        $this->autoRender = false;

        //Authenticate
        $loggedInUser = $this->authenticateUser(apache_request_headers());
        if (empty($loggedInUser)) {
            $this->response->statusCode(401);
            return json_encode(
                array(
                    'message' => 'User is not authenticated',
                    'data' => null
                )
            );
        }

        //Get request data
        $requestData = $this->request->input('json_decode');
        if (empty($requestData)) {
            $this->response->statusCode(400);
            return json_encode(
                array(
                    'message' => 'Request must have data',
                    'data' => null
                )
            );
        }

        $users = $this->User->findUsersNotFromIds($requestData->ids);

        function getUsers($users){
            return $users['User'];
        }

        $userList = array_map('getUsers', $users);

        return json_encode(
            array(
                'message' => 'User list',
                'data' => $userList
            )
        );
    }


    public function deleteMessage(){
        $this->response->type('json');
        $this->autoRender = false;

        //Authenticate
        $loggedInUser = $this->authenticateUser(apache_request_headers());
        if (empty($loggedInUser)) {
            $this->response->statusCode(401);
            return json_encode(
                array(
                    'message' => 'User is not authenticated',
                    'data' => null
                )
            );
        }

        //Get request data
        $requestData = $this->request->input('json_decode');
        if (empty($requestData)) {
            $this->response->statusCode(400);
            return json_encode(
                array(
                    'message' => 'Request must have data',
                    'data' => null
                )
            );
        }

        $fc = new FirebaseComponent();

        return $fc->deleteMessage($requestData, $loggedInUser);
    }

    public function deleteConversation(){
        $this->response->type('json');
        $this->autoRender = false;

        //Authenticate
        $loggedInUser = $this->authenticateUser(apache_request_headers());
        if (empty($loggedInUser)) {
            $this->response->statusCode(401);
            return json_encode(
                array(
                    'message' => 'User is not authenticated',
                    'data' => null
                )
            );
        }

        //Get request data
        $requestData = $this->request->input('json_decode');
        if (empty($requestData)) {
            $this->response->statusCode(400);
            return json_encode(
                array(
                    'message' => 'Request must have data',
                    'data' => null
                )
            );
        }

        $fc = new FirebaseComponent();

        return $fc->deleteConversation($requestData, $loggedInUser);
    }

}