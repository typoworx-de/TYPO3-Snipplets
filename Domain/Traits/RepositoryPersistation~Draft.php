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

    protected $updateEntities = [];

    protected $removeEntities = [];


    public function initializeObject()
    {
        /** @var $querySettings \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings */
        $querySettings = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Flushs the database-table
     */
    public function flush()
    {
        /** ConnectionPool */
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_kuno_domain_model_bankcodes')
            ->truncate('tx_kuno_domain_model_bankcodes')
        ;
    }

    /**
     * Use Doctrine to speed-up persistation
     */
    public function persist() : void
    {
        foreach($this->addedEntities as $object)
        {
            $tableName = $this->resolveDomainModelTable($object);
            $objectQueryBuilder = $this->getQueryBuilderForTable($tableName);

            if($objectQueryBuilder !== null)
            {
                $objectVars = get_class_vars($object);

                die(var_dump($objectVars));

                if ($object->_isNew())
                {
                    $objectQueryBuilder
                        ->insert($tableName)
                        ->values($objectVars)
                        ->execute()
                    ;
                }
            }
        }
    }

    public function add($object)
    {
        $this->addedEntities[] = $object;
    }

    public function update($object)
    {
        $this->updateEntities[] = $object;
    }

    public function remove($object)
    {
        $this->removeEntities[] = $object;
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
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    protected function getQueryBuilderForTable(string $tableName) : \TYPO3\CMS\Core\Database\Query\QueryBuilder
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
                    ->createQueryBuilder()
                ;
            }
            else
            {
                $objectMappings[ $tableName ] = null;
            }
        }

        return $objectMappings[ $tableName ];
    }
}
