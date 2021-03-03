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

namespace W7\Mqtt\Handler;

use W7\Http\Message\Server\Request;
use W7\Mqtt\Message\ConnAckMessage;
use W7\Mqtt\Message\PubCompMessage;
use W7\Mqtt\Message\PubRelMessage;
use W7\Mqtt\Message\SubAckMessage;
use W7\Mqtt\Message\UnSubAckMessage;

interface HandlerInterface {
	public function onMqConnect(Request $request) : ConnAckMessage;
	public function onMqPing(Request $request): bool;
	public function onMqDisconnect(Request $request): bool;
	public function onMqPublish(Request $request);
	public function onMqPublishRec(Request $request) : PubRelMessage;
	public function onMqPublishRel(Request $request) : PubCompMessage;
	public function onMqSubscribe(Request $request) : SubAckMessage;
	public function onMqUnSubscribe(Request $request) : UnSubAckMessage;
}
