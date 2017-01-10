<?php
/**
 * PHPCI - Continuous Integration for PHP
 *
 * @copyright    Copyright 2014, Block 8 Limited.
 * @license      https://github.com/Block8/PHPCI/blob/master/LICENSE.md
 * @link         https://www.phptesting.org/
 */

namespace PHPCensor\Plugin;

use PHPCensor;
use PHPCensor\Builder;
use PHPCensor\Model\Build;
use PHPCensor\Plugin;
use PHPCensor\ZeroConfigPlugin;

/**
 * PHP Loc - Allows PHP Copy / Lines of Code testing.
 * 
 * @author       Johan van der Heide <info@japaveh.nl>
 * @package      PHPCI
 * @subpackage   Plugins
 */
class PhpLoc extends Plugin implements ZeroConfigPlugin
{
    /**
     * @var string
     */
    protected $directory;

    /**
     * Check if this plugin can be executed.
     * @param $stage
     * @param Builder $builder
     * @param Build $build
     * @return bool
     */
    public static function canExecute($stage, Builder $builder, Build $build)
    {
        if ($stage == 'test') {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $builder, Build $build, array $options = [])
    {
        parent::__construct($builder, $build, $options);

        $this->directory = $this->builder->buildPath;

        if (isset($options['directory'])) {
            $this->directory .= $options['directory'];
        }
    }

    /**
     * Runs PHP Copy/Paste Detector in a specified directory.
     */
    public function execute()
    {
        $ignore = '';

        if (count($this->builder->ignore)) {
            $map = function ($item) {
                return ' --exclude ' . rtrim($item, DIRECTORY_SEPARATOR);
            };

            $ignore = array_map($map, $this->builder->ignore);
            $ignore = implode('', $ignore);
        }

        $phploc = $this->builder->findBinary('phploc');

        $success = $this->builder->executeCommand($phploc . ' %s "%s"', $ignore, $this->directory);
        $output  = $this->builder->getLastOutput();

        if (preg_match_all('/\((LOC|CLOC|NCLOC|LLOC)\)\s+([0-9]+)/', $output, $matches)) {
            $data = [];
            foreach ($matches[1] as $k => $v) {
                $data[$v] = (int)$matches[2][$k];
            }

            $this->build->storeMeta('phploc', $data);
        }

        return $success;
    }
}
