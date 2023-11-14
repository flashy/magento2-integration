<?php

namespace Flashy\Integration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\Manager as CacheManager;

class SetEnvironmentCommand extends Command
{
    const ENV_ARGUMENT = 'environment';
    const FLASHY_ENV_PATH = 'flashy/general/environment';

    protected $configWriter;
    protected $cacheManager;

    public function __construct(
        WriterInterface $configWriter,
        CacheManager $cacheManager
    ) {
        $this->configWriter = $configWriter;
        $this->cacheManager = $cacheManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('flashy:set-environment')
            ->setDescription('Set the Flashy environment (production/dev)')
            ->setDefinition([
                new InputArgument(
                    self::ENV_ARGUMENT,
                    InputArgument::REQUIRED,
                    'Environment'
                )
            ]);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
	{
		$environment = $input->getArgument(self::ENV_ARGUMENT);

		if (!in_array($environment, ['dev', 'production'])) {
			$output->writeln("<error>Invalid environment. Please specify 'production' or 'dev'.</error>");
			return 0; // Ensure an integer is returned for errors. This constant is available from Symfony 5.1
		}

		// Save the new setting
		$this->configWriter->save(self::FLASHY_ENV_PATH, $environment);

		// Clean the cache so that the new configuration is used
		$this->cacheManager->clean(['config']);

		$output->writeln("<info>Environment set to '{$environment}'.</info>");
		return 1; // Ensure an integer is returned on success. This constant is available from Symfony 5.1
	}

}
