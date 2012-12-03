<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieślik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver;

use FSi\Component\DataSource\Exception\DataSourceException;

/**
 * {@inheritdoc}
 */
abstract class DriverAbstract implements DriverInterface
{
    /**
     * Extensions.
     *
     * @var array
     */
    protected $extensions = array();

    /**
     * Field types.
     *
     * @var array
     */
    protected $fieldTypes = array();

    /**
     * Fields extensions.
     *
     * @var array
     */
    protected $fieldExtensions = array();

    /**
     * Constructor.
     *
     * @throws DataSourceException
     * @param $extensions array with extensions
     */
    public function __construct($extensions = array())
    {
        if (!is_array($extensions)) {
            throw new DataSourceException(sprintf('Array of extensions expected, "%s" given.', gettype($extensions)));
        }

        foreach ($extensions as $extension) {
            if (!($extension instanceof DriverExtensionInterface)) {
                throw new DataSourceException(sprintf('Instance of DriverExtensionInterface expected, "%s" given.', get_class($extension)));
            }
        }

        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function hasFieldType($type)
    {
        $this->initFieldType($type);
        return isset($this->fieldTypes[$type]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldType($type)
    {
        if (!$this->hasFieldType($type)) {
            throw new DataSourceException(sprintf('Unsupported field type ("%s").', $type));
        }

        $field = clone $this->fieldTypes[$type];

        foreach ($this->fieldExtensions[$type] as $extension) {
            $field->addExtension(clone $extension);
        }

        return $field;
    }

    /**
     * Inits field for given type (including extending that type) and saves it as pattern for later cloning.
     *
     * @param string $type
     */
    private function initFieldType($type)
    {
        if (isset($this->fieldTypes[$type])) {
            return;
        }

        $typeInstance = false;
        foreach ($this->extensions as $extension) {
            if ($extension->hasFieldType($type)) {
                $typeInstance = $extension->getFieldType($type);
                break;
            }
        }

        if (!$typeInstance) {
            return;
        }

        $this->fieldTypes[$type] = $typeInstance;

        $ext = array();
        foreach ($this->extensions as $extension) {
            if ($extension->hasFieldTypeExtensions($type)) {
                $fieldExtensions = $extension->getFieldTypeExtensions($type);
                foreach ($fieldExtensions as $fieldExtension) {
                    $ext[] = $fieldExtension;
                }
            }
        }

        $this->fieldExtensions[$type] = $ext;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function addExtension(DriverExtensionInterface $extension)
    {
        $this->extensions[] = $extension;
    }
}