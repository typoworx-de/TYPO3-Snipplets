<?php
namespace Typoworx\Foo\Domain\Repository;

use Typoworx\Foo\Utility\NamespaceUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/***
 *
 * This file is part of the "Kuno Portal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Gabriel Kaufmann <info@typoworx.de>
 *
 ***/

trait FooBarTrait
{
    protected $addedEntities = [];
    
    protected $updatedEntities = [];

    protected $removedEntities = [];


    public function add($object)
    {
        $this->addedEntities[] = $object;
    }

    public function update($object)
    {
        $this->updatedEntities[] = $object;
    }

    public function remove($object)
    {
        $this->removedEntities[] = $object;
    }

    /**
     * @param mixed $object
     * @return string|null
     */
    protected function resolveDomainModelTable($object)
    {
        static $mappings;

        if($mappings === null)
        {
            $mappings = [];
        }

        $namespace = get_class($object);
        if(!isset($mappings[ $namespace ]))
        {
            $objectNamespace = NamespaceUtility::getVanishedNamespace($namespace);

            if(empty($objectNamespace) || empty($objectNamespace['extension']) || empty($objectNamespace['model']))
            {
                $mappings[ $objectNamespace ] = null;
            }
            else
            {
                $mappings[ $namespace ] = sprintf(
                    'tx_%s_domain_model_%s',
                    strtolower($objectNamespace['extension']),
                    strtolower($objectNamespace['model'])
                );
            }
        }

        return isset($mappings[ $namespace ])
            ? $mappings[ $namespace ]
            : null
            ;
    }

    /**
     * @param string $tableName
     * @return \TYPO3\CMS\Core\Database\Connection
     */
    protected function getQueryBuilderForTable(string $tableName) : \TYPO3\CMS\Core\Database\Connection
    {
        static $objectMappings;

        if($objectMappings === null)
        {
            $objectMappings = [];
        }

        if(!isset($objectMappings[ $tableName ]))
        {
            if($tableName !== null)
            {
                $objectMappings[ $tableName ] = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable($tableName)
                ;
            }
            else
            {
                $objectMappings[ $tableName ] = null;
            }
        }

        return $objectMappings[ $tableName ];
    }

    /**
     * @param $object
     * @return array|null
     * @throws \ReflectionException
     * @throws \TYPO3\CMS\Extbase\Reflection\Exception
     */
    protected function getPersistableProperties($object)
    {
        $properties = null;

        $objectName = get_class($object);
        if(!isset($objectPropertyMapper[ $objectName ]))
        {
            $reflection = new ClassReflection($object);

            $properties = [];
            foreach($reflection->getProperties() as $property)
            {
                $propertyName = GeneralUtility::camelCaseToLowerCaseUnderscored($property->getName());
                $value = $property->getValue($object);

                if($value !== NULL)
                {
                    $properties[ $propertyName ] = $value;
                }
            }
        }

        return $properties;
    }
}
