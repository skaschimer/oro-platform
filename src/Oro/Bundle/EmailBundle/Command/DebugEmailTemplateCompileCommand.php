<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Yaml\Yaml;

/**
 * Renders an email template.
 * Optionally, sends a compiled email to the email address specified in the "recipient" option.
 */
#[AsCommand(
    name: 'oro:debug:email:template:compile',
    description: 'Renders an email template. Optionally, sends a compiled email' .
        'to the email address specified in the "recipient" option.'
)]
class DebugEmailTemplateCompileCommand extends Command
{
    private DoctrineHelper $doctrineHelper;

    private EmailTemplateProvider $emailTemplateProvider;

    private EmailRenderer $emailRenderer;

    private MailerInterface $mailer;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EmailTemplateProvider $emailTemplateProvider,
        EmailRenderer $emailRenderer,
        MailerInterface $mailer
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->emailTemplateProvider = $emailTemplateProvider;
        $this->emailRenderer = $emailRenderer;
        $this->mailer = $mailer;

        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument(
                'template',
                InputArgument::REQUIRED,
                'The name of email template to be compiled.'
            )
            ->addOption(
                'params-file',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to YML file with params for compilation.'
            )
            ->addOption(
                'entity-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'An entity ID.'
            )
            ->addOption(
                'recipient',
                null,
                InputOption::VALUE_OPTIONAL,
                'Recipient email address. [Default: null]',
                null
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $templateName = $input->getArgument('template');

        $emailTemplate = $this->emailTemplateProvider->loadEmailTemplate(new EmailTemplateCriteria($templateName));
        if ($emailTemplate === null) {
            $output->writeln(sprintf('Email template "%s" is not found', $templateName));

            return Command::FAILURE;
        }

        $templateParams = $this->getNormalizedParams($input->getOption('params-file'));
        if ($emailTemplate->getEntityName()) {
            $templateParams['entity'] = $this->getEntity(
                $emailTemplate->getEntityName(),
                $input->getOption('entity-id')
            );
        }

        $renderedEmailTemplate = $this->emailRenderer->renderEmailTemplate($emailTemplate, $templateParams);

        if (!$input->getOption('recipient')) {
            $output->writeln(sprintf('SUBJECT: %s', $renderedEmailTemplate->getSubject()));
            $output->writeln('');
            $output->writeln('BODY:');
            $output->writeln($renderedEmailTemplate->getContent());
        } else {
            $emailMessage = (new Email())
                ->subject($renderedEmailTemplate->getSubject());

            if ($emailTemplate->getType() === EmailTemplateInterface::TYPE_HTML) {
                $emailMessage->html($renderedEmailTemplate->getContent());
            } else {
                $emailMessage->text($renderedEmailTemplate->getContent());
            }

            $emailMessage->from($input->getOption('recipient'));
            $emailMessage->to($input->getOption('recipient'));

            try {
                $this->mailer->send($emailMessage);
                $output->writeln(sprintf('Message was successfully sent to "%s"', $input->getOption('recipient')));
            } catch (\RuntimeException $e) {
                $output->writeln(sprintf('Message was not sent due to error: "%s"', $e->getMessage()));
            }
        }

        return Command::SUCCESS;
    }

    private function getNormalizedParams(?string $paramsFile = null): array
    {
        if ($paramsFile && is_file($paramsFile) && is_readable($paramsFile)) {
            return Yaml::parse(file_get_contents($paramsFile));
        }

        return [];
    }

    /**
     * @param string $entityClass
     * @param null|mixed $entityId
     *
     * @return object
     */
    private function getEntity(string $entityClass, $entityId = null)
    {
        $entity = $this->doctrineHelper->createEntityInstance($entityClass);

        if ($entityId) {
            $entity = $this->doctrineHelper->getEntity($entityClass, $entityId);

            if (!$entity) {
                throw new \RuntimeException(sprintf('Entity "%s" with id "%s" not found', $entityClass, $entityId));
            }
        }

        return $entity;
    }
}
