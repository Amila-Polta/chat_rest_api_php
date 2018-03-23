<?php



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
                "'.$uOneId.'/'.$thread_id.'/timeStamp" : "'.time().'",
                "'.$uOneId.'/'.$thread_id.'/type" : "Private",
                "'.$uOneId.'/'.$thread_id.'/unseenCount" :  "0",
                "'.$uOneId.'/'.$thread_id.'/user" : "'.$uTwoId.'",
                "'.$uTwoId.'/'.$thread_id.'/createdTime" : "'.time().'",
                "'.$uTwoId.'/'.$thread_id.'/displayName" : "'.$userOne['User']['first_name'].' '.$userOne['User']['last_name'].'",
                "'.$uTwoId.'/'.$thread_id.'/threadId" : "'.$thread_id.'",
                "'.$uTwoId.'/'.$thread_id.'/timeStamp" : "'.time().'",
                "'.$uTwoId.'/'.$thread_id.'/type" :  "Private",
                "'.$uTwoId.'/'.$thread_id.'/unseenCount" :  "0",
                "'.$uTwoId.'/'.$thread_id.'/user" : "'.$uOneId.'"
            }';

        return $this->makeHttpRequest($url, $post_body, 'PATCH');
    }


    public function createGroupThreads ($requestData) {

        //Generate group
        $url = 'https://practera-notification.firebaseio.com/groups.json';
        $group_id = json_decode($this->makeHttpRequest($url, json_encode($requestData), 'POST'));

        $thread_id = $group_id->name;

        //Add threads to users
        $url = 'https://practera-notification.firebaseio.com/.json';

        //Create post body
        $post_body = '{
                "groups/'.$thread_id.'/groupId" : "'.$thread_id.'",
                "groups/'.$thread_id.'/createTime" : "'.time().'",';

        foreach($requestData['members'] as $key => $val) {
            $post_body = $post_body.'
                "threads/'.$key.'/'.$thread_id.'/createdTime" : "'.time().'",
                "threads/'.$key.'/'.$thread_id.'/displayName" : "'.$requestData['name'].'",
                "threads/'.$key.'/'.$thread_id.'/threadId" : "'.$thread_id.'",
                "threads/'.$key.'/'.$thread_id.'/timeStamp" : "'.time().'",
                "threads/'.$key.'/'.$thread_id.'/type" : "Group",
                "threads/'.$key.'/'.$thread_id.'/unseenCount" :  "0",
                "threads/'.$key.'/'.$thread_id.'/groupId" : "'.$thread_id.'",';
        }

        $post_body = $post_body.'"groups/'.$thread_id.'/updateTime" : "'.time().'"
            }';


        return $this->makeHttpRequest($url, $post_body, 'PATCH');

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

}