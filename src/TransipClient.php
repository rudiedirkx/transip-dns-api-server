<?php

class TransipClient {

	const BASE = 'https://api.transip.nl/v6/';

	protected $token;

	public $lastJson = '';

	public function __construct(string $token) {
		$this->token = $token;
	}

	public function dnsGet(string $domain) : array {
		$rsp = $this->get("domains/$domain/dns");
		return $rsp['dnsEntries'];
	}

	public function dnsAdd(string $domain, string $name, string $type, string $value, ?int $ttl = null) : void {
		$rsp = $this->post("domains/$domain/dns", [
			'dnsEntry' => [
				'name' => $name,
				'type' => strtoupper($type),
				'content' => $value,
				'expire' => $ttl ?? 3600,
			],
		]);
	}

	public function dnsRemove(string $domain, string $name, string $type, string $value, int $ttl) : void {
		$rsp = $this->delete("domains/$domain/dns", [
			'dnsEntry' => [
				'name' => $name,
				'type' => strtoupper($type),
				'content' => $value,
				'expire' => $ttl,
			],
		]);
	}

	protected function get(string $path) : array {
		return $this->request($path, []);
	}

	protected function post(string $path, array $data) : array {
		return $this->request($path, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode($data),
		]);
	}

	protected function delete(string $path, array $data) : array {
		return $this->request($path, [
			CURLOPT_CUSTOMREQUEST => 'DELETE',
			CURLOPT_POSTFIELDS => json_encode($data),
		]);
	}

	protected function request(string $path, array $options) : array {
		$curl = curl_init();
		curl_setopt_array($curl, $options + [
			CURLOPT_URL => self::BASE . $path,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Authorization: Bearer ' . $this->token
			],
		]);

		$this->lastJson = curl_exec($curl);
		curl_close($curl);
		return json_decode($this->lastJson, true) ?: [];
	}

}
