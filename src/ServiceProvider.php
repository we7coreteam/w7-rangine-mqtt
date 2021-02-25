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

namespace W7\Mqtt;

use W7\Console\Application;
use W7\Core\Provider\ProviderAbstract;
use W7\Core\Server\ServerEvent;
use W7\Mqtt\Listener\CloseListener;
use W7\Mqtt\Listener\ConnectListener;
use W7\Mqtt\Listener\ReceiveListener;
use W7\Mqtt\Server\Server;

class ServiceProvider extends ProviderAbstract {
	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register() {
		$this->registerMqttServer();
	}

	private function registerMqttServer() {
		$this->registerServer('mqtt', Server::class);
		$this->registerServerEvent('mqtt', [
			ServerEvent::ON_RECEIVE => ReceiveListener::class,
			ServerEvent::ON_CONNECT => ConnectListener::class,
			ServerEvent::ON_CLOSE => CloseListener::class
		]);
	}

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot() {
	}

	public function providers(): array {
		return [Application::class];
	}
}
