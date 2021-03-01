<?php

/**
 * Rangine mqtt server
 *
 * (c) We7Team 2019 <https://www.rangine.com>
 *
 * document http://s.w7.cc/index.php?c=wiki&do=view&id=317&list=2284
 *
 * visited https://www.rangine.com for more details
 */

namespace W7\Mqtt\Server;

use RuntimeException;
use Simps\MQTT\Protocol\V3;
use Simps\MQTT\Protocol\V5;
use W7\App;
use W7\Tcp\Server\Server as TcpServer;

class Server extends TcpServer {
	public static $aloneServer = true;
	public static $supportProtocol = [
		'v3' => V3::class,
		'v5' => V5::class
	];

	public function __construct() {
		parent::__construct();
		App::$mqttServer = $this;
	}

	public function getType() {
		return 'mqtt';
	}

	protected function checkSetting() {
		parent::checkSetting();

		if (empty($this->setting['handler'])) {
			throw new RuntimeException('The MQTT service Handler configuration is missing');
		}

		$protocol = $this->setting['protocol'] ?? 'v3';
		if (!in_array($protocol, array_keys(self::$supportProtocol))) {
			throw new RuntimeException('This protocol is not currently supported by the MQTT service');
		}

		$this->setting['protocol'] = $protocol;
		$this->setting['open_mqtt_protocol'] = true;
	}

	public function listener(\Swoole\Server $server) {
		if ($server->port != $this->setting['port']) {
			$this->server = $server->addListener($this->setting['host'], $this->setting['port'], $this->setting['sock_type']);
			//tcp需要强制关闭其它协议支持，否则继续父服务
			$this->server->set([
				'open_http2_protocol' => false,
				'open_http_protocol' => false,
				'open_websocket_protocol' => false,
				'open_mqtt_protocol' => true
			]);
		} else {
			$this->server = $server;
		}

		$this->registerService();
	}
}
