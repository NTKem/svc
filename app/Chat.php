<?php
namespace App;
 use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Models\Chat as ModelChat;

class Chat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $querystring = $conn->httpRequest->getUri()->getQuery();

        parse_str($querystring, $queryarray);

        if(isset($queryarray['token']))
        {
            $conn->resourceId = $queryarray['token'];
            $this->clients->attach($conn);
            $data['type'] = 'update_user';
            $data['uuid'] = $queryarray['token'];
            $data['connect_id'] = $conn->resourceId;
        }
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        $data = json_decode($msg, true);

        if($data['command'] == 'private')
        {
            $post = [
                'chat_uuid'=>$data['command'],
                'uuid_user' => $data['uuid_user'],
                "uuid_user_to"=>$data['uuid_user_to'],
                "message"=>$data['message'],
                "seen"=>0
            ];
            $chat = ModelChat::create($post);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}
