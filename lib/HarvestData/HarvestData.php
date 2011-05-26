<?php

namespace HarvestData;

use HarvestData\Command\FetchToday;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Application;

class HarvestData extends \Symfony\Component\Console\Application {

	public function __construct() {
		parent::__construct('HarvestData', '0.1');
		$this->addCommands(array(new FetchToday()));
	}

}