@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    {{Auth::user()->uuid}}
                    {{ __('You are logged in!') }}
                        <h1>Simple Chat using WebSocket + Ratchet</h1>
                        <p>
                            Open It in two browser tabs and see It working!
                        </p>
                        <div id="chat"></div>
                        <textarea id="message" onkeyup="sendMessage(event)" placeholder="Type your message here..."></textarea>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="application/javascript">
    var conn = new WebSocket('ws://localhost:8080?token={{Auth::user()->uuid}}');
    conn.onopen = () => {

        console.log("Connection established!");
    };
    function getTime()
    {
        var date = new Date();
        return date.toLocaleDateString()
    };
    function sendMessage(e)
    {
        var code = (e.keyCode ? e.keyCode : e.which);
        if(code !== 13) {
            return;
        }
        var message = document.getElementById('message').value;
        if (message.length == 0) {
            return;
        }
        var data = {
            uuid_user: '6c729ca5-f773-468c-b838-930908bf788b',
            message: message,
            uuid_user_to:'4db9956c-af43-4efa-92b8-4e7244705acd',
            command:'message'
        };

        conn.send(JSON.stringify(data));
        var content = document.getElementById('chat').innerHTML;
        document.getElementById('chat').innerHTML = content + '<div class="sent-message">Sent on [' + getTime() + '] ' + message + '</div>';
        document.getElementById('message').value = '';
    };
    function receiveMessage(e)
    {
        var content = document.getElementById('chat').innerHTML;
        document.getElementById('chat').innerHTML = content + '<div class="received-message">Received on [' + getTime() + '] ' + e.data + '</div>';
    };
    conn.onmessage = function(e)
    {
        console.log(e);
        var data = JSON.parse(e.data);
        if(data.type === 'connect'){
            var data_json = {
                uuid_user: '6c729ca5-f773-468c-b838-930908bf788b',
                uuid_user_to:'4db9956c-af43-4efa-92b8-4e7244705acd',
                command:'load_message'
            };
            conn.send(JSON.stringify(data_json));
        }else if(data.type === 'load_message'){
            receiveMessage(e);
        }
    };
</script>
@endsection
