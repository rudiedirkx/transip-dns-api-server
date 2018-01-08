<?php

use Transip\Client;
use Transip\Model\DnsEntry;

require __DIR__ . '/vendor/autoload.php';

header('Content-type: text/plain; charset=utf-8');

function err($msg) {
	header('HTTP/1.1 400 Invalid');
	exit("$msg\n");
}

function client() {
	$client = new Client($_POST['username'], $_POST['key'], false, 'api.transip.nl');
	return $client->api('domain');
}

if ( !isset($_POST['username'], $_POST['key']) ) {
	err("Need username & key.");
}

$d = $_POST;

if ( !isset($d['domain'], $d['type'], $d['name']) ) {
	err("Missing params");
}

if ( isset($d['add']) ) {
	if ( !isset($d['value']) ) {
		err("Missing params");
	}

	$client = client();

	try {
		$records = $client->getInfo($d['domain'])->dnsEntries;
		$record = new DnsEntry($d['name'], @$d['ttl'] ?: 3600, strtoupper($d['type']), $d['value']);
		$records[] = $record;

		$client->setDnsEntries($d['domain'], $records);
	}
	catch ( Exception $ex ) {
		err($ex->getMessage());
	}

	exit("Record added.\n");
}

elseif ( isset($d['delete']) ) {
	$client = client();

	try {
		$before = $client->getInfo($d['domain'])->dnsEntries;

		$removed = array_filter($before, function(DnsEntry $record) use ($d) {
			if ( $record->type == strtoupper($d['type']) && $record->name == $d['name'] ) {
				if ( empty($d['value']) || $record->content == $d['value'] ) {
					if ( empty($d['ttl']) || $record->expire == $d['ttl'] ) {
						return false;
					}
				}
			}
			return true;
		});

		if ( count($before) != count($removed) ) {
			$client->setDnsEntries($d['domain'], array_values($removed));
		}
	}
	catch ( Exception $ex ) {
		err($ex->getMessage());
	}

	exit("Record deleted.\n");
}

err("Only 'add' & 'delete' supported.");
