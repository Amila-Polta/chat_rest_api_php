<?php

/**
 * Created by PhpStorm.
 * User: amila
 * Date: 3/28/18
 * Time: 3:30 PM
 */
class SendPushNotification {

    public function sendPushNotificationPrivate($requestData, $loggedInUser, $pushTokens){

        $notification = '{
            "notification" : {
                "title" : "'.$loggedInUser['User']['first_name'].' '.$loggedInUser['User']['last_name'].'",
                "body" : "'.$requestData->text.'",
                "tag" : "'.$requestData->thread_id.'"
                },
            "registration_ids" : '.$pushTokens.'
            }';

        $this->sendNotifications($notification);

    }

    public function sendPushNotificationGroup($requestData, $pushTokens){
        $notification = '{
            "notification" : {
                "title" : "You have a new message",
                "body" : "'.$requestData->text.'",
                "tag" : "'.$requestData->thread_id.'"
                },
            "registration_ids" : '.$pushTokens.'
            }';

        $this->sendNotifications($notification);
    }



    public function sendNotifications ($body)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://fcm.googleapis.com/fcm/send",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array(
                "content-type: application/json",
                "Authorization: key=AAAA8UZcyJQ:APA91bG6BPAx_SxK03_YdJ-zrnj6ox4gClhswOk_o_GQprMJzzQjwXSyaB4e-ODfSRh2gOIEZoESjW8MDlejfX_mGNXfX-lf2HMF5w5fI5dttkjM-qKhPNX7GxXtFNgdhlnp0s3SSrpp"
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