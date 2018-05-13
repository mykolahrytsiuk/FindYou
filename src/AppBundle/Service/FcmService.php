<?php

namespace AppBundle\Service;


use AppBundle\Entity\Notification;

class FcmService
{
    private $apiKey = 'AAAA4wynn7c:APA91bH7-FIVeQ5W7lDUEs37pgZwFm0w75iiV4AWLaohJW3zY76PHb8uqA0bDTRlkLoS5ThMhS62xIwY2tHmKCLG_3stL7c8suRfV9M0Hr0j9hPbGE5p30Ll9PTKf7qtlpScksbjk4Rd';
    private $url = 'https://fcm.googleapis.com/fcm/send';
    private $schema;
    private $domain;

    public function __construct($schema, $domain)
    {
        $this->schema = $schema;
        $this->domain = $domain;
    }

    public function sendNotification(Notification $notification)
    {
        $request_body = [
            'to' => $notification->getReceiver()->getFirebaseToken(),
            'notification' => [
                'title' => $notification->getTitle(),
                'body' =>  $notification->getBody(),
                'icon' => $this->schema.'://'.$this->domain.'/'.$notification->getAuthor()->getPhoto()->getPath(),
                'click_action' => $notification->getLink(),
            ],
            "data" => [
                "type" => $notification->getType()
            ]
        ];

        $fields = json_encode($request_body);

        $request_headers = [
            'Content-Type: application/json',
            'Authorization: key=' . $this->apiKey,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

}