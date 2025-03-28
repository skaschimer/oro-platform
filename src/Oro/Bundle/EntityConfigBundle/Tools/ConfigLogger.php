<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * Adds to logger entity config log
 */
class ConfigLogger extends AbstractLogger
{
    /**
     * @var LoggerInterface
     */
    protected $baseLogger;

    public function __construct(LoggerInterface $baseLogger)
    {
        $this->baseLogger = $baseLogger;
    }

    #[\Override]
    public function log($level, $message, array $context = [])
    {
        $this->baseLogger->log($level, $message, $context);

        if (isset($context['configs'])) {
            $this->logConfigs($context['configs'], $level);
        }
    }

    /**
     * @param array  $configs
     * @param mixed  $level
     * @param string $indent
     */
    protected function logConfigs($configs, $level, $indent = '')
    {
        if (!empty($configs)) {
            foreach ($configs as $key => $val) {
                if (is_array($val)) {
                    $this->baseLogger->log(
                        $level,
                        sprintf('%s"%s"', $indent, $key)
                    );
                    $this->logConfigs($val, $level, $indent . '  ');
                } else {
                    $this->baseLogger->log(
                        $level,
                        sprintf('%s"%s" = %s', $indent, $key, $this->convertToStr($val))
                    );
                }
            }
        }
    }

    /**
     * @param mixed $val
     * @return string
     */
    protected function convertToStr($val)
    {
        if (null === $val) {
            return 'null';
        }
        if (is_string($val)) {
            return sprintf('"%s"', $val);
        }
        if (is_bool($val)) {
            return $val ? 'true' : 'false';
        }
        if (is_object($val) && is_callable([$val, '__serialize'])) {
            return serialize($val);
        }

        return (string)$val;
    }
}
