<?php
date_default_timezone_set("Asia/Shanghai");
session_start();
$server = new \swoole_websocket_server("0.0.0.0", 39010);
$server->on('open', function (swoole_websocket_server $server, $request) {
	$data = json_decode(json_encode($request),1);
	$uname=$data["server"]["path_info"];
	if($uname=="/slog_123456")
	{
		$_SESSION["debuguser"]=$request->fd;
		echo "{$uname}发来的消息: handshake success with fd".$request->fd."\n";
	}
	else
	{
		echo "server: handshake success with fd".$request->fd."\n";
	}
    
});
$server->on('message', function (swoole_websocket_server $server, $frame) {
    echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
    $server->push($frame->fd, "this is server");
    //$server->push($_SESSION["debuguser"], $frame->data);
});
$server->on('close', function ($ser, $fd) {
    echo "client {$fd} closed\n";
});

$server->start();
