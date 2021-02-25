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

use Simps\MQTT\Protocol\Types;
use Simps\MQTT\Protocol\V3;
use W7\Core\Dispatcher\RequestDispatcher;
use W7\Http\Message\Server\Request;
use W7\Http\Message\Server\Response;

class Dispatcher extends RequestDispatcher {
	public function dispatch(...$params) {
		/**
		 * @var Request $psr7Request
		 * @var Response $psr7Response
		 */
		$psr7Request = $params[0];
		$psr7Response = $params[1];
		$this->getContext()->setRequest($psr7Request);
		$this->getContext()->setResponse($psr7Response);
		$this->getContext()->setContextDataByKey('server-type', $this->serverType);

		try {
			$data = V3::unpack($psr7Request->getBody()->getContents());
			if (is_array($data) && isset($data['type'])) {
				switch ($data['type']) {
					case Types::PINGREQ: // 心跳请求
						[$class, $func] = $this->_config['receiveCallbacks'][Types::PINGREQ];
						$obj = new $class();
						if ($obj->{$func}($server, $fd, $fromId, $data)) {
							// 返回心跳响应
							$server->send($fd, Protocol::pack(['type' => Types::PINGRESP]));
						}
						break;
					case Types::DISCONNECT: // 客户端断开连接
						[$class, $func] = $this->_config['receiveCallbacks'][Types::DISCONNECT];
						$obj = new $class();
						if ($obj->{$func}($server, $fd, $fromId, $data)) {
							if ($server->exist($fd)) {
								$server->close($fd);
							}
						}
						break;
					case Types::CONNECT: // 连接
					case Types::PUBLISH: // 发布消息
					case Types::SUBSCRIBE: // 订阅
					case Types::UNSUBSCRIBE: // 取消订阅
						[$class, $func] = $this->_config['receiveCallbacks'][$data['type']];
						$obj = new $class();
						$obj->{$func}($server, $fd, $fromId, $data);
						break;
				}
			} else {
				$server->close($fd);
			}
		} catch (\Throwable $e) {
			$server->close($fd);
		}
	}
}
