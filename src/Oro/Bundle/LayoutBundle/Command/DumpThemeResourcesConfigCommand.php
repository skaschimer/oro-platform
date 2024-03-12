<?php

declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Command;

use Oro\Component\Config\ResourcesContainer;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ThemeResourceProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Dumps the layout theme resource configuration generated by ThemeResourceProvider.
 */
class DumpThemeResourcesConfigCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:layout:theme-resource-config:dump';

    private ThemeResourceProvider $themeConfiguration;

    public function __construct(ThemeResourceProvider $themeResourceProvider)
    {
        parent::__construct();
        $this->themeConfiguration = $themeResourceProvider;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->setDescription('Dumps the layout theme resource configuration generated by ThemeResourceProvider.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> layout theme resource configuration generated
by <comment>ThemeResourceProvider</comment>.

  <info>php %command.full_name%</info>

HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, $output);

        $config = $this->themeConfiguration->loadAndGetConfig(new ResourcesContainer());
        $output->writeln(json_encode($config));

        return Command::SUCCESS;
    }
}
