<?php

App::uses('SendPushNotification', 'Controller/Component');

class FirebaseComponent {

    /**
     * @param $userOne
     * @param $userTwo
     * @return mixed|string
     */
    public function createOneToOneChat ($userOne, $userTwo) {
        $url = 'https://practera-notification.firebaseio.com/threads.json';

        //Create thread id
        $uOneId = $userOne['User']['id'];
        $uTwoId = $userTwo['User']['id'];
        $thread_id = '';
        if ($uOneId > $uTwoId) {
            $thread_id = $uTwoId.'_'.$uOneId;
        } else {
            $thread_id = $uOneId.'_'.$uTwoId;
        }

        //Create post body
        $post_body = '{
                "'.$uOneId.'/'.$thread_id.'/createdTime" : "'.time().'",
                "'.$uOneId.'/'.$thread_id.'/displayName" : "'.$userTwo['User']['first_name'].' '.$userTwo['User']['last_name'].'",
                "'.$uOneId.'/'.$thread_id.'/threadId" : "'.$thread_id.'",
                "'.$uOneId.'/'.$thread_id.'/image" : "http://moorehumane.org/wp-content/uploads/2016/06/avatar-male.jpg",
                "'.$uOneId.'/'.$thread_id.'/timeStamp" : "'.time().'",
                "'.$uOneId.'/'.$thread_id.'/type" : "Private",
                "'.$uOneId.'/'.$thread_id.'/unseenCount" :  "0",
                "'.$uOneId.'/'.$thread_id.'/user" : "'.$uTwoId.'",
                "'.$uTwoId.'/'.$thread_id.'/createdTime" : "'.time().'",
                "'.$uTwoId.'/'.$thread_id.'/displayName" : "'.$userOne['User']['first_name'].' '.$userOne['User']['last_name'].'",
                "'.$uTwoId.'/'.$thread_id.'/threadId" : "'.$thread_id.'",
                "'.$uTwoId.'/'.$thread_id.'/image" : "http://moorehumane.org/wp-content/uploads/2016/06/avatar-male.jpg",
                "'.$uTwoId.'/'.$thread_id.'/timeStamp" : "'.time().'",
                "'.$uTwoId.'/'.$thread_id.'/type" :  "Private",
                "'.$uTwoId.'/'.$thread_id.'/unseenCount" :  "0",
                "'.$uTwoId.'/'.$thread_id.'/user" : "'.$uOneId.'"
            }';

        return $this->makeHttpRequest($url, $post_body, 'PATCH');
    }


    public function createGroupThreads ($requestData, $pushTokens) {

        //Generate group
        $url = 'https://practera-notification.firebaseio.com/groups.json';
        $group_id = json_decode($this->makeHttpRequest($url, json_encode($requestData), 'POST'));

        $thread_id = $group_id->name;

        //Add threads to users
        $url = 'https://practera-notification.firebaseio.com/.json';

        //Create post body
        $post_body = '{
                "groups/'.$thread_id.'/groupId" : "'.$thread_id.'",
                "groups/'.$thread_id.'/image" : "http://guguia.net/wp-content/plugins/wp-recall/add-on/groups/img/group-avatar.png",
                "groups/'.$thread_id.'/createTime" : "'.time().'",';

        foreach($requestData['members'] as $key => $val) {
            $post_body = $post_body.'
                "threads/'.$key.'/'.$thread_id.'/createdTime" : "'.time().'",
                "threads/'.$key.'/'.$thread_id.'/displayName" : "'.$requestData['name'].'",
                "threads/'.$key.'/'.$thread_id.'/threadId" : "'.$thread_id.'",
                "threads/'.$key.'/'.$thread_id.'/image" : "http://guguia.net/wp-content/plugins/wp-recall/add-on/groups/img/group-avatar.png",
                "threads/'.$key.'/'.$thread_id.'/timeStamp" : "'.time().'",
                "threads/'.$key.'/'.$thread_id.'/type" : "Group",
                "threads/'.$key.'/'.$thread_id.'/unseenCount" :  "0",
                "threads/'.$key.'/'.$thread_id.'/groupId" : "'.$thread_id.'",';
        }

        $post_body = $post_body.'"groups/'.$thread_id.'/updateTime" : "'.time().'"
            }';

        $response = $this->makeHttpRequest($url, $post_body, 'PATCH');

        $spn = new SendPushNotification();

        $spn->sendPushNotificationCreateGroup($requestData, $thread_id, $pushTokens);

        return $response;

    }

    public function sendMessage ($loggedUser, $requestData) {

//        if ($requestData->message_type === 'Group') {
//            $url = 'https://practera-notification.firebaseio.com/groups/' . $requestData->threadId . '.json';
//
//            $group = json_decode($this->makeHttpRequest($url, '', 'GET'), true);
//
//            if (empty($group)) {
//                return null;
//            }
//
//            $adminIds = array_keys($group['members'], "admin", false);
//            $userIds = array_keys($group['members'], "member", false);
//
//            foreach ($adminIds as $adminId) {
//                array_push($userIds, $adminId);
//            }
//
//
//            $key = array_search($loggedUser['User']['id'], $userIds);
//
//
//
//        }

        $loggedUserId = $loggedUser['User']['id'];
        $threadId = $requestData->thread_id;
        $messageText = $requestData->text;

        //Create message id
        $url = 'https://practera-notification.firebaseio.com/messages/'.$loggedUserId.'/'.$threadId.'/.json';

        $post_body = '{}';

        $message_id_response = json_decode($this->makeHttpRequest($url, $post_body, 'POST'));

        $message_id = $message_id_response->name;


        //Create post body
        $post_body = '{
                "messages/'.$loggedUserId.'/'.$threadId.'/'.$message_id.'/messageId" : "'.$message_id.'",
                "messages/'.$loggedUserId.'/'.$threadId.'/'.$message_id.'/senderId" : "'.$loggedUserId.'",
                "messages/'.$loggedUserId.'/'.$threadId.'/'.$message_id.'/text" : "'.$messageText.'",
                "messages/'.$loggedUserId.'/'.$threadId.'/'.$message_id.'/timeStamp" : "'.time().'",
                "messages/'.$loggedUserId.'/'.$threadId.'/'.$message_id.'/type" : "text",';

        foreach ($requestData->recipient as $userId) {

            $url = 'https://practera-notification.firebaseio.com/threads/'.$userId.'/'.$threadId.'.json';

            $thread = json_decode($this->makeHttpRequest($url, '', 'GET'));

                $post_body = $post_body.'"messages/'.$userId.'/'.$threadId.'/'.$message_id.'/messageId" : "'.$message_id.'",
                "messages/'.$userId.'/'.$threadId.'/'.$message_id.'/senderId" : "'.$loggedUserId.'",
                "messages/'.$userId.'/'.$threadId.'/'.$message_id.'/text" : "'.$messageText.'",
                "messages/'.$userId.'/'.$threadId.'/'.$message_id.'/timeStamp" : "'.time().'",
                "messages/'.$userId.'/'.$threadId.'/'.$message_id.'/type" : "text",
                "threads/'.$userId.'/'.$threadId.'/timeStamp" : "'.time().'",
                "threads/'.$userId.'/'.$threadId.'/lastMessage" : "'.$messageText.'",
                "threads/'.$userId.'/'.$threadId.'/senderId" : "'.$loggedUserId.'",
                "threads/'.$userId.'/'.$threadId.'/unseenCount" : "'.($thread->unseenCount +1).'",';
        }

        $post_body = $post_body.'"threads/'.$loggedUserId.'/'.$threadId.'/timeStamp" : "'.time().'",
                "threads/'.$loggedUserId.'/'.$threadId.'/lastMessage" : "'.$messageText.'",
                "threads/'.$loggedUserId.'/'.$threadId.'/senderId" : "'.$loggedUserId.'",
                "threads/'.$loggedUserId.'/'.$threadId.'/unseenCount" : "0"
                }';

        $url = 'https://practera-notification.firebaseio.com/.json';

        return $this->makeHttpRequest($url, $post_body, 'PATCH');

    }


    public function removeUserFromGroup ($userId, $requestData) {

        $url = 'https://practera-notification.firebaseio.com/.json';

        $post_body = '{
            "groups/'.$requestData->group_id.'/members" : '.json_encode($requestData->members).',
            "messages/'.$userId.'/'.$requestData->group_id.'" : {},
            "threads/'.$userId.'/'.$requestData->group_id.'" : {}
        }';

        return $this->makeHttpRequest($url, $post_body, 'PATCH');
    }

    public function editGroup ($requestData) {

        $url = 'https://practera-notification.firebaseio.com/groups/'.$requestData->group_id.'.json';

        $group = json_decode($this->makeHttpRequest($url, '', 'GET'), true);

        if (empty($group)) {
            return null;
        }

        $adminIds = array_keys($group['members'], "admin", false);
        $userIds = array_keys($group['members'], "member", false);

        foreach ($adminIds as $adminId) {
            array_push($userIds, $adminId);
        }

        $url = 'https://practera-notification.firebaseio.com/.json';

        $body = '{';
        if (isset($requestData->name)){
            $body = $body.'"groups/'.$requestData->group_id.'/name" : "'.$requestData->name.'",';
            foreach ($userIds as $userId) {
                $body = $body.'"threads/'.$userId.'/'.$requestData->group_id.'/displayName" : "'.$requestData->name.'",
                "threads/'.$userId.'/'.$requestData->group_id.'/image" : "http://guguia.net/wp-content/plugins/wp-recall/add-on/groups/img/group-avatar.png",';
            }
        }
        if (isset($requestData->image)){
            $body = $body.'"groups/'.$requestData->group_id.'/image" : "'.$requestData->image.'",';
        }
        $body = $body.'"groups/'.$requestData->group_id.'/updateTime" : "'.time().'"
        }';

        return $this->makeHttpRequest($url, $body, 'PATCH');
    }


    public function addPeopleToGroup ($requestData) {

        $userIds = $requestData->user_ids;
        $thread_id = $requestData->thread_id;
        $url = 'https://practera-notification.firebaseio.com/.json';

        $post_body = '{';
        foreach ($userIds as $userId) {
            $post_body = $post_body.'"threads/'.$userId.'/'.$thread_id.'/createdTime" : "'.time().'",
                "threads/'.$userId.'/'.$thread_id.'/displayName" : "'.$requestData->name.'",
                "threads/'.$userId.'/'.$thread_id.'/threadId" : "'.$thread_id.'",
                "threads/'.$userId.'/'.$thread_id.'/timeStamp" : "'.time().'",
                "threads/'.$userId.'/'.$thread_id.'/type" : "Group",
                "threads/'.$userId.'/'.$thread_id.'/unseenCount" :  "0",
                "threads/'.$userId.'/'.$thread_id.'/groupId" : "'.$thread_id.'",';
            }
        $post_body = $post_body.'"groups/'.$thread_id.'/members" : '.json_encode($requestData->members).'
        }';

        return $this->makeHttpRequest($url, $post_body, 'PATCH');
    }


    public function createHelpDeskChat($loggedInUser) {

        $userId = $loggedInUser['User']['id'];
        $name = $loggedInUser['User']['first_name'].' '.$loggedInUser['User']['last_name'];

        $url = 'https://practera-notification.firebaseio.com/.json';


        $post_body = '{
                "threads/helpDesk/'.$userId.'/createdTime" : "'.time().'",
                "threads/helpDesk/'.$userId.'/displayName" : "'.$name.'",
                "threads/helpDesk/'.$userId.'/user" : "'.$userId.'",
                "threads/helpDesk/'.$userId.'/threadId" : "'.$userId.'",
                "threads/helpDesk/'.$userId.'/timeStamp" : "'.time().'",
                "threads/helpDesk/'.$userId.'/type" : "Help Desk",
                "threads/helpDesk/'.$userId.'/unseenCount" :  "0"
        }';

        return $this->makeHttpRequest($url, $post_body, 'PATCH');

    }

    public function sendHelpDeskMessage($requestData, $loggedInUser, $pushTokens){
        $userId = $requestData->thread_id;
        $url = 'https://practera-notification.firebaseio.com/threads/helpDesk/'.$userId.'/.json';

        $thread = json_decode($this->makeHttpRequest($url, '', 'GET'), true);

        $helpDeskCount = '0';
        $unseenCount = '0';
        $type = '';
        if ($userId === $loggedInUser['User']['id']) {
            $helpDeskCount = $thread['helpDeskCount'] + 1 ;
            $type = 'User';
        } else {
            $unseenCount = $thread['unseenCount'] + 1 ;
            $type = 'HelpDesk';
        }
        //Create message id
        $url = 'https://practera-notification.firebaseio.com/messages/helpDesk/'.$userId.'/.json';

        $post_body = '{}';

        $message_id_response = json_decode($this->makeHttpRequest($url, $post_body, 'POST'));

        $message_id = $message_id_response->name;

        $url = 'https://practera-notification.firebaseio.com/.json';


        $post_body = '{
                "threads/helpDesk/'.$userId.'/userId" : "'.$userId.'",
                "threads/helpDesk/'.$userId.'/timeStamp" : "'.time().'",
                "threads/helpDesk/'.$userId.'/lastMessage" : "'.$requestData->text.'",
                "threads/helpDesk/'.$userId.'/senderId" : "'.$loggedInUser['User']['id'].'",
                "threads/helpDesk/'.$userId.'/helpDeskCount" : "'.$helpDeskCount.'",
                "threads/helpDesk/'.$userId.'/unseenCount" :  "'.$unseenCount.'",
                "messages/helpDesk/'.$userId.'/'.$message_id.'/messageId" : "'.$message_id.'",
                "messages/helpDesk/'.$userId.'/'.$message_id.'/senderId" : "'.$loggedInUser['User']['id'].'",
                "messages/helpDesk/'.$userId.'/'.$message_id.'/text" : "'.$requestData->text.'",
                "messages/helpDesk/'.$userId.'/'.$message_id.'/timeStamp" : "'.time().'",
                "messages/helpDesk/'.$userId.'/'.$message_id.'/from" : "'.$type.'",
                "messages/helpDesk/'.$userId.'/'.$message_id.'/type" : "text"
        }';


        $response = $this->makeHttpRequest($url, $post_body, 'PATCH');

        $spn = new SendPushNotification();

        $spn->sendPushNotificationHelpDesk($requestData, $loggedInUser, $pushTokens);

        return $response;
    }


    public function deleteMessage($requestData, $loggedInUser){
        $userIdList = [];
        if ($requestData->message_type === 'Group'){
            $url = 'https://practera-notification.firebaseio.com/groups/'.$requestData->thread_id.'.json';

            $group = json_decode($this->makeHttpRequest($url, '', 'GET'), true);

            if (empty($group)) {
                return null;
            }

            $adminIds = array_keys($group['members'], "admin", false);
            $userIds = array_keys($group['members'], "member", false);

            foreach ($adminIds as $adminId) {
                array_push($userIds, $adminId);
            }
            $userIdList = $userIds;
        } else {
            $userIdList = $requestData->user_ids;
        }

        $threadId = $requestData->thread_id;
        $messageId = $requestData->message_id;


        $url = 'https://practera-notification.firebaseio.com/.json';

        $body = '{';
        foreach ($userIdList as $userId) {

            $body = $body.'"messages/'.$userId.'/'.$threadId.'/'.$messageId.'" : {},';
        }

        $body = $body.'"messages/'.$loggedInUser['User']['id'].'/'.$threadId.'/'.$messageId.'" : {}
            }';

        return $this->makeHttpRequest($url, $body, 'PATCH');
    }

    public function deleteConversation($requestData, $loggedInUser){


        $url = 'https://practera-notification.firebaseio.com/.json';

        $body = '{
                "messages/'.$loggedInUser['User']['id'].'/'.$requestData->thread_id.'" : {},
                "threads/'.$loggedInUser['User']['id'].'/'.$requestData->thread_id.'" : {},
                "messages/'.$requestData->user_id.'/'.$requestData->thread_id.'" : {},
                "threads/'.$requestData->user_id.'/'.$requestData->thread_id.'" : {}
            }';

        return $this->makeHttpRequest($url, $body, 'PATCH');
    }

    /**
     * @param $url
     * @param $body
     * @param $method
     * @return mixed|string
     */
    public function makeHttpRequest ($url, $body, $method) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array(
                "content-type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }


    public function getOtherUsersInThread($message_type, $threadId, $loggedInUser){

        $loggedInUserId = $loggedInUser['User']['id'];

        if ($message_type === 'Group') {
            $url = 'https://practera-notification.firebaseio.com/groups/' . $threadId . '.json';

            $group = json_decode($this->makeHttpRequest($url, '', 'GET'), true);

            if (empty($group)) {
                return null;
            }

            $adminIds = array_keys($group['members'], "admin", false);
            $userIds = array_keys($group['members'], "member", false);

            foreach ($adminIds as $adminId) {
                array_push($userIds, $adminId);
            }


            $key = array_search($loggedInUserId, $userIds);
            if ($key === 0){
                if (empty($userIds[$key])){
                    return null;
                }
            } else if (empty($key)){
                return null;
            }
            unset($userIds[$key]);
            return $userIds;
        }


    }

}