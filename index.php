<?php

require __DIR__ . '/vendor/autoload.php';

header('Content-type: text/plain; charset=utf-8');

function err($msg) {
	header('HTTP/1.1 400 Invalid');
	exit("$msg\n");
}

if ( !isset($_POST['username'], $_POST['key']) ) {
	err("Need username & key.");
}

$d = $_POST;

if ( !isset($d['domain'], $d['type'], $d['name']) ) {
	err("Missing params");
}

// $token = trim(@file_get_contents($tokenFile = __DIR__ . '/token.txt') ?: '');
// if ( !$token ) {
	$auth = new TransIP_AccessToken($_POST['username'], $_POST['key'], 'rdx/transip-api-server/' . rand());
	$token = $auth->createToken();
// 	file_put_contents($tokenFile, $token);
// }

$client = new TransipClient($token);

if ( isset($d['add']) ) {
	if ( !isset($d['value']) ) {
		err("Missing params");
	}

	$client->dnsAdd($d['domain'], $d['name'], $d['type'], $d['value'], $d['ttl'] ?? null);

	exit("Record added.\n");
}

elseif ( isset($d['delete']) ) {
	$records = $client->dnsGet($d['domain']);

	foreach ( $records as $record ) {
		if ( $record['name'] == $d['name'] && $record['type'] == strtoupper($d['type']) ) {
			if ( empty($d['ttl']) || $record['expire'] == $d['ttl'] ) {
				if ( empty($d['value']) || $record['content'] == $d['value'] ) {
					$client->dnsRemove($d['domain'], $d['name'], $d['type'], $record['content'], $record['expire']);
				}
			}
		}
	}

	exit("Record deleted.\n");
}

err("Only 'add' & 'delete' supported.");
