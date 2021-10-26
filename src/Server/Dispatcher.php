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

use Simps\MQTT\Protocol\ProtocolInterface;
use Simps\MQTT\Protocol\Types;
use W7\App;
use W7\Core\Dispatcher\RequestDispatcher;
use W7\Core\Exception\HandlerExceptions;
use W7\Http\Message\Server\Request;
use W7\Http\Message\Server\Response;
use W7\Mqtt\Handler\HandlerInterface;
use W7\Mqtt\Message\PingRespMessage;

class Dispatcher extends RequestDispatcher {
	public function dispatch(...$params) {
		/**
		 * @var Request $psr7Request
		 * @var Response $psr7Response
		 */
		$psr7Request = $params[0];
		$psr7Response = $params[1];
		$this->getContext()->setResponse($psr7Response);
		$this->getContext()->setContextDataByKey('server-type', $this->serverType);

		try {
			/**
			 * @var Server $mqttServer
			 */
			$mqttServer = App::getApp()->mqttServer;
			/**
			 * @var ProtocolInterface $protocol
			 */
			$protocol = Server::$supportProtocol[$mqttServer->setting['protocol']];
			$data = $protocol::unpack($psr7Request->getBody()->getContents());
			if (is_array($data) && isset($data['type'])) {
				$psr7Request = $psr7Request->withParsedBody($data);
				$this->getContext()->setRequest($psr7Request);
				/**
				 * @var HandlerInterface $handler
				 */
				$handler = $this->getContainer()->get($mqttServer->setting['handler']);
				switch ($data['type']) {
					case Types::PINGREQ: // 心跳请求
						if ($handler->onMqPing($psr7Request)) {
							// 返回心跳响应
							$psr7Response = $psr7Response->withContent(new PingRespMessage());
						}
						break;
					case Types::DISCONNECT: // 客户端断开连接
						if ($handler->onMqDisconnect($psr7Request) && App::$server->getServer()->exist($this->getContext()->getContextDataByKey('fd'))) {
							$psr7Response = $psr7Response->close();
						}
						break;
					case Types::CONNECT:
						$message = $handler->onMqConnect($psr7Request);
						$psr7Response = $psr7Response->withContent($message);
						break;
					case Types::PUBLISH: // 发布消息
						$message = $handler->onMqPublish($psr7Request);
						$message && $psr7Response =  $psr7Response->withContent($message);
						break;
					case Types::PUBREC: // 消息发布收到报文
						$message = $handler->onMqPublishRec($psr7Request);
						$psr7Response = $psr7Response->withContent($message);
						break;
					case Types::PUBREL: // 消息发布释放
						$message = $handler->onMqPublishRel($psr7Request);
						$message && $psr7Response = $psr7Response->withContent($message);
						break;
					case Types::SUBSCRIBE: // 订阅
						$message = $handler->onMqSubscribe($psr7Request);
						$psr7Response = $psr7Response->withContent($message);
						break;
					case Types::UNSUBSCRIBE: // 取消订阅
						$message = $handler->onMqUnSubscribe($psr7Request);
						$psr7Response = $psr7Response->withContent($message);
						break;
				}
			} else {
				$psr7Response = $psr7Response->close();
			}
		} catch (\Throwable $e) {
			$this->getContainer()->get(HandlerExceptions::class)->getHandler()->report($e);
			$psr7Response = $psr7Response->close();
		}

		return $psr7Response;
	}
}
