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

namespace W7\Mqtt\Listener;

use Swoole\Server;
use W7\Core\Listener\ListenerAbstract;
use W7\Core\Server\ServerEvent;
use W7\Http\Message\Server\Request as Psr7Request;
use W7\Http\Message\Server\Response as Psr7Response;
use W7\Mqtt\Collector\FdCollector;
use W7\Mqtt\Server\Dispatcher as RequestDispatcher;

class ReceiveListener extends ListenerAbstract {
	public function run(...$params) {
		list($server, $fd, $reactorId, $data) = $params;

		$this->dispatch($server, $reactorId, $fd, $data);
	}

	private function dispatch(Server $server, $reactorId, $fd, $data) {
		$this->getContext()->setContextDataByKey('fd', $fd);
		$this->getContext()->setContextDataByKey('reactorid', $reactorId);
		$this->getContext()->setContextDataByKey('workid', $server->worker_id);
		$this->getContext()->setContextDataByKey('coid', $this->getContext()->getCoroutineId());

		$collector = FdCollector::instance()->get($fd, []);

		/**
		 * @var Psr7Request $psr7Request
		 */
		$psr7Request = $collector[0];
		$psr7Request = $psr7Request->loadFromTcpData($data);
		/**
		 * @var Psr7Response $psr7Response
		 */
		$psr7Response = $collector[1];

		$this->getEventDispatcher()->dispatch(ServerEvent::ON_USER_BEFORE_REQUEST, [$psr7Request, $psr7Response, 'mqtt']);

		/**
		 * @var RequestDispatcher $dispatcher
		 */
		$dispatcher = $this->getContainer()->singleton(RequestDispatcher::class);
		$psr7Response = $dispatcher->dispatch($psr7Request, $psr7Response);

		$this->getEventDispatcher()->dispatch(ServerEvent::ON_USER_AFTER_REQUEST, [$psr7Request, $psr7Response, 'mqtt']);

		$psr7Response->send();
	}
}
