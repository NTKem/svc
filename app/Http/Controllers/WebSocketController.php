<?php
namespace App\Http\Controllers;

use App\Models\Chat as ModelChat;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * @author Rohit Dhiman | @aimflaiims
 */
class WebSocketController implements MessageComponentInterface
{
    protected $clients;
    private $subscriptions;
    private $users;
    private $userresources;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
        $this->users = [];
        $this->userresources = [];
    }

    /**
     * [onOpen description]
     * @method onOpen
     * @param  ConnectionInterface $conn [description]
     * @return [JSON]                    [description]
     * @example connection               var conn = new WebSocket('ws://localhost:8090');
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $querystring = $conn->httpRequest->getUri()->getQuery();
        parse_str($querystring, $queryarray);
        if(isset($queryarray['token'])) {
            $conn->resourceId = $queryarray['token'];
            $this->clients->attach($conn);
            $this->users[$conn->resourceId] = $conn;
            $data['type'] = 'connect';
            $conn->send(json_encode($data));
        }
    }

    /**
     * [onMessage description]
     * @method onMessage
     * @param  ConnectionInterface $conn [description]
     * @param  [JSON.stringify]              $msg  [description]
     * @return [JSON]                    [description]
     * @example subscribe                conn.send(JSON.stringify({command: "subscribe", channel: "global"}));
     * @example groupchat                conn.send(JSON.stringify({command: "groupchat", message: "hello glob", channel: "global"}));
     * @example message                  conn.send(JSON.stringify({command: "message", to: "1", from: "9", message: "it needs xss protection"}));
     * @example register                 conn.send(JSON.stringify({command: "register", userId: 9}));
     */
    public function onMessage(ConnectionInterface $conn, $msg)
    {
        echo $msg;
        $data = json_decode($msg);
        if (isset($data->command)) {
            switch ($data->command) {
                case "subscribe":
                    $this->subscriptions[$conn->resourceId] = $data->channel;
                    break;
                case "groupchat":
                    //
                    // $conn->send(json_encode($this->subscriptions));
                    if (isset($this->subscriptions[$conn->resourceId])) {
                        $target = $this->subscriptions[$conn->resourceId];
                        foreach ($this->subscriptions as $id=>$channel) {
                            if ($channel == $target && $id != $conn->resourceId) {
                                $this->users[$id]->send($data->message);
                            }
                        }
                    }
                    break;
                case "message":
                    if ( isset($this->users[$data->uuid_user_to]) ) {
                        $this->users[$data->uuid_user_to]->send($msg);
                    }

                    if (isset($this->userresources[$data->uuid_user])) {
                        $this->users[$data->uuid_user]->send($msg);
                    }
                    $post = [
                        'chat_uuid'=>$data->command,
                        'uuid_user' => $data->uuid_user,
                        "uuid_user_to"=>$data->uuid_user_to,
                        "message"=>$data->message,
                        "seen"=>0
                    ];
                    ModelChat::create($post);
                    break;
                case "load_message":
                        $all = $this->get_chat_limit($data->uuid_user,$data->uuid_user_to);
                        $data_json = [
                          'data'=>$all,
                          'type'  =>'load_message'
                        ];
                    $this->users[$data->uuid_user]->send(json_encode($data_json));
                    break;

                case "register":
                    //
                    if (isset($data->userId)) {
                        if (isset($this->userresources[$data->userId])) {
                            if (!in_array($conn->resourceId, $this->userresources[$data->userId]))
                            {
                                $this->userresources[$data->userId][] = $conn->resourceId;
                            }
                        }else{
                            $this->userresources[$data->userId] = [];
                            $this->userresources[$data->userId][] = $conn->resourceId;
                        }
                    }
                    $conn->send(json_encode($this->users));
                    $conn->send(json_encode($this->userresources));
                    break;
                default:
                    $example = array(
                        'methods' => [
                            "subscribe" => '{command: "subscribe", channel: "global"}',
                            "groupchat" => '{command: "groupchat", message: "hello glob", channel: "global"}',
                            "message" => '{command: "message", to: "1", message: "it needs xss protection"}',
                            "register" => '{command: "register", userId: 9}',
                        ],
                    );
                    $conn->send(json_encode($example));
                    break;
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
        unset($this->users[$conn->resourceId]);
        unset($this->subscriptions[$conn->resourceId]);

        foreach ($this->userresources as &$userId) {
            foreach ($userId as $key => $resourceId) {
                if ($resourceId==$conn->resourceId) {
                    unset( $userId[ $key ] );
                }
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    private function get_chat($uuid_user,$uuid_user_to,$limit = null,$offset = null){
        $sql = ModelChat::where('uuid_user', '=', $uuid_user, 'and')->where('uuid_user_to', '=', $uuid_user_to, 'and')
            ->where('uuid_user', '=', $uuid_user_to,'or')->where('uuid_user_to','=',$uuid_user);
        $sql->orderBy('created_at');
        if (!empty($limit)){
            $sql->limit($limit);
        }
        if (!empty($offset)){
            $sql->offset($offset);
        }
        return $sql->get();
    }

    private function get_chat_limit($uuid_user,$uuid_user_to,$limit = 10,$offset = 0){
        $count = count($this->get_chat($uuid_user,$uuid_user_to));
        $offset = $count - ($limit + $offset);
        return $this->get_chat($uuid_user,$uuid_user_to,$limit,$offset);
    }
}
