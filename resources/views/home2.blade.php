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
    var conn = new WebSocket('ws://localhost:8080');
    conn.onopen = function(e)
    {
        console.log("Connection established!");
        var data = {
            userId: '{{Auth::user()->uuid}}',
            command:'register'
        };

        conn.send(JSON.stringify(data));
    };
</script>
@endsection
