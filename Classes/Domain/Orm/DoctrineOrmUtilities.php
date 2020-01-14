<?php
namespace Typoworx\FooBar\Domain\Orm;

use Doctrine\DBAL\Driver\Statement;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\ClassNamingUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

use Typoworx\FooBar\Utility\StaticObjectManager;
use Typoworx\FooBar\Domain\Collection\ObjectCollection;

/**
 * Class DoctrineOrmUtilities
 */
class DoctrineOrmUtilities
{
    const DATE_TIME_UTC = 'Y-m-d H:i:s';

    /**
     * @var array <\Typoworx\FooBar\Domain\Orm\DoctrineOrmUtilities>
     */
    protected static $_instances = [];

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var \TYPO3\CMS\Core\Database\Query\QueryBuilder|\Doctrine\DBAL\Driver\Statement
     */
    public $currentQuery;


#region Constructor
    /**
     * DoctrineOrmUtilities Trait-Constructor
     */
    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @param string $tableName
     * @return mixed
     */
    public static function getInstance(string $tableName) : self
    {
        if(self::$_instances[ $tableName ] === null)
        {
            self::$_instances[ $tableName ] = StaticObjectManager::get(get_called_class(), $tableName);
        }

        return self::$_instances[ $tableName ];
    }

    /**
     * @return string|null
     */
    public function getTableName() :? string
    {
        return $this->tableName;
    }

    /**
     * @param string $columnName
     * @return bool
     */
    public function hasTcaColumn(string $columnName) : bool
    {
        if($columnName === 'deleted')
        {
            return isset($GLOBALS['TCA'][ $this->tableName ]['ctrl'][ $columnName ]);
        }

        return isset($GLOBALS['TCA'][ $this->tableName ]['columns'][ $columnName]);
    }

    /**
     * @return array
     */
    public function getTcaColumns() : array
    {
        return array_keys($GLOBALS['TCA'][ $this->tableName ]['columns']);
    }
#endregion

#region ORM Wrapper Functions
    /**
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    public function createQuery() : QueryBuilder
    {
        $this->currentQuery = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        $this->andWhereDeleted(false);

        return $this->currentQuery;
    }

    /**
     * @note !!! Testing required !!!
     * @param string $sql
     * @return \Doctrine\DBAL\Driver\Statement
     * @throws \Doctrine\DBAL\DBALException
     */
    public function sqlQuery(string $sql) : Statement
    {
        $query = $this->createQuery();

        $types = [];
        foreach($query->getParameters() as $parameter)
        {
            $types[] = gettype($parameter);
        }

        $statement = $query->getConnection()->executeQuery($sql, $query->getParameters(), $types);

        return $statement;
    }

    /**
     * @return bool|int
     */
    public function rowCount()
    {
        if(is_bool($this->currentQuery))
        {
            return $this->currentQuery;
        }
        else if(is_int($this->currentQuery))
        {
            return $this->currentQuery;
        }
        else if($this->currentQuery instanceof \Doctrine\DBAL\Driver\Statement)
        {
            return $this->currentQuery->rowCount();
        }
        else if($this->currentQuery instanceof \TYPO3\CMS\Core\Database\Query\QueryBuilder)
        {
            return $this->currentQuery->getConcreteQueryBuilder()->execute()->rowCount();
        }

        return false;
    }

    /**
     * @return bool|\Doctrine\DBAL\Driver\Statement|int
     */
    public function execute()
    {
        if($this->currentQuery instanceof \Doctrine\DBAL\Driver\Statement)
        {
            if($this->currentQuery->execute())
            {
                return $this->currentQuery;
            }
        }
        else if($this->currentQuery instanceof \TYPO3\CMS\Core\Database\Query\QueryBuilder)
        {
            return $this->currentQuery->getConcreteQueryBuilder()->execute();
        }

        // Clean-Up
        $this->currentQuery = null;

        return false;
    }

    /**
     * @param array $entity
     * @return array|null
     * @throws \Exception
     */
    public function save(array $entity) :? array
    {
        if(!empty($entity['uid']))
        {
            $this->updateByUid($entity)->execute();
        }

        return $this->insertQuery($entity);
    }

    /**
     * Fetches first result
     * @return mixed
     */
    public function fetchFirst()
    {
        $row = null;

        if($this->currentQuery instanceof QueryBuilder)
        {
            $row = $this->currentQuery->execute()->fetch();
        }
        else if($this->currentQuery instanceof \Doctrine\DBAL\Driver\Statement)
        {
            if($this->currentQuery->execute())
            {
                $row = $this->currentQuery->fetchAll();
            }
        }

        return count($row) ? $row : null;
    }

    /**
     * @return int
     */
    public function fetchCount() : int
    {
        $result = $this->fetchFirst();

        return count($result) ? (int)array_shift($result) : null;
    }

    /**
     * @param array|null $entity
     * @param int $limit
     * @param callable|null $andWhereCallback
     * @param bool $softDelete
     */
    public function deleteQuery(array $entity = null, $limit = 1, callable $andWhereCallback = null, $softDelete = true) : void
    {
        if($softDelete === true)
        {
            if(1 || $this->hasTcaColumn('deleted'))
            {
                // Soft Delete record using 'deleted' flag
                $query = $this->createQuery();
                $query->update($this->tableName);

                if (!empty($entity['uid']))
                {
                    $this->andWhereUid($entity['uid']);
                }

                $this->andWhereByCallback($andWhereCallback, $query);

                $query->execute();
            }
        }
        else
        {
            // Physical delete record
            $query = $this->createQuery();
            $query->delete($this->tableName);

            if (!empty($entity['uid']))
            {
                $this->andWhereUid($entity['uid']);
            }

            $this->andWhereByCallback($andWhereCallback, $query);
            $query->setMaxResults($limit);

            $this->currentQuery = $query;
            $query->execute();
        }
    }

    /**
     * @param string|null $sqlDeleteWhere
     * @param int $limit
     * @return bool
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function physicalDeleteQuery(string $sqlDeleteWhere = null, $limit = 1) : bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class);

        /** @var Connection $tableConnection */
        $tableConnection = $connection->getConnectionForTable($this->tableName);
        $tableConnection->beginTransaction();

        try
        {
            $tableConnection->query('SET FOREIGN_KEY_CHECKS=0');
            $tableConnection->query(sprintf('DELETE FROM %s WHERE %s LIMIT %d', $this->tableName, $sqlDeleteWhere, $limit));
            $tableConnection->query('SET FOREIGN_KEY_CHECKS=1');

            $tableConnection->commit();

            return true;
        }
        catch (\Exception $e)
        {
            $tableConnection->rollBack();

            throw $e;
        }

        return false;
    }

    public function getTableConnection() : Connection
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class);

        return $connection->getConnectionForTable($this->tableName);
    }

    /**
     * @return \Doctrine\DBAL\Driver\Statement|\TYPO3\CMS\Core\Database\Query\QueryBuilder|null
     */
    public function getCurrent()
    {
        return $this->currentQuery;
    }

    /**
     * @param \TYPO3\CMS\Core\Database\Query\QueryBuilder|null $query
     * @return string|null
     * @throws \Exception
     */
    public function getDebugSql(\TYPO3\CMS\Core\Database\Query\QueryBuilder $query = null) :? string
    {
        if($query === null)
        {
            $query = $this->currentQuery;
        }

        if(empty($query))
        {
            throw new \Exception('Got empty Query! Specify as argument if none given.');
        }

        $preparedBindings = $query->getParameters();
        foreach($preparedBindings as $key => $value)
        {
            unset($preparedBindings[$key]);

            if(is_null($value))
            {
                $value = 'null';
            }
            else if(is_string($value))
            {
                $value = sprintf('"%s"', $value);
            }
            else if (is_array($value))
            {
                if (is_string(current($value)))
                {
                    $value = sprintf('"%s"', implode('","', $value));
                }
                else
                {
                    $value = implode(',', $value);
                }
            }

            $preparedBindings[':' . $key] = $value;
        }

        return str_replace(array_keys($preparedBindings), $preparedBindings, $query->getSQL());
    }

    /**
     * !! Still experimental, requires testing !!
     * @param \TYPO3\CMS\Core\Database\Query\QueryBuilder $query
     * @param array $filters
     */
    public static function buildFilters(\TYPO3\CMS\Core\Database\Query\QueryBuilder &$query, array $filters = null)
    {
        if(count($filters))
        {
            foreach($filters as $field => $value)
            {
                $type = null;
                if(is_int($value) || is_bool($value))
                {
                    $type = \PDO::PARAM_INT;
                    $value = (int)$value;
                }
                else if(is_string($value))
                {
                    $type = \PDO::PARAM_STR;
                }

                if($type !== null)
                {
                    $query->andWhere(
                        $query->expr()->eq(
                            $field,
                            $query->createNamedParameter($value, $type)
                        )
                    );
                }
            }
        }
    }

    /**
     * @param bool $deleted
     * @return \Mosaiq\MqTools\Domain\Orm\DoctrineOrmUtilities
     */
    public function andWhereDeleted(bool $deleted) : self
    {
        if($this->hasTcaColumn('deleted'))
        {
            $this->currentQuery->andWhere(
                $this->currentQuery->expr()->eq(
                    'deleted',
                    $this->currentQuery->createNamedParameter((int)$deleted, \PDO::PARAM_INT)
                )
            );
        }

        return $this;
    }

    /**
     * @param bool $hidden
     * @return \Mosaiq\MqTools\Domain\Orm\DoctrineOrmUtilities
     */
    public function andWhereHidden(bool $hidden) : self
    {
        if($this->hasTcaColumn('hidden'))
        {
            $this->currentQuery->andWhere(
                $this->currentQuery->expr()->eq(
                    'hidden',
                    $this->currentQuery->createNamedParameter((int)$hidden, \PDO::PARAM_INT)
                )
            );
        }

        return $this;
    }

    /**
     * @param int|null $uid
     * @return \Mosaiq\MqTools\Domain\Orm\DoctrineOrmUtilities
     */
    public function andWhereUid($uid) : self
    {
        if(!empty($uid) && $this->currentQuery !== null)
        {
            $this->currentQuery->andWhere(
                $this->currentQuery->expr()->eq(
                    'uid',
                    $this->currentQuery->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            );
        }

        return $this;
    }


    /**
     * @param mixed ...$where
     * @return \Mosaiq\MqTools\Domain\Orm\DoctrineOrmUtilities
     */
    public function andWhere(...$where) : self
    {
        $this->getCurrent()->andWhere($where);

        return $this;
    }

    /**
     * @param $callable
     * @param \TYPO3\CMS\Core\Database\Query\QueryBuilder|null $query
     * @return \Mosaiq\MqTools\Domain\Orm\DoctrineOrmUtilities
     */
    public function andWhereByCallback($callable, QueryBuilder $query = null) : self
    {
        if($query === null)
        {
            $query = $this->currentQuery;
        }

        if(is_callable($callable))
        {
            call_user_func($callable, $query, $this);
        }

        return $this;
    }

    /**
     * @param array $updateArray
     * @param null $andWhereCallback
     * @return \Mosaiq\MqTools\Domain\Orm\DoctrineOrmUtilities
     * @throws \Exception
     */
    public function updateByUid(array $updateArray, $andWhereCallback = null) : self
    {
        if(!$updateArray['uid'])
        {
            throw new \Exception('Given object doesn\'t look like valid record-array (missing Uid-Property)');
        }

        $query = $this->createQuery();
        $query
            ->update($this->tableName)
            ->where(
                $query->expr()->eq(
                    'uid',
                    $query->createNamedParameter($updateArray['uid'], \PDO::PARAM_INT)
                )
            )
        ;

        foreach($updateArray as $field => $value)
        {
            // Prevent modification of Uid!
            if($field === 'uid')
            {
                continue;
            }

            /**
             * Patch:
             * MySQL may convert Bool to String instead of int
             * (caused by strict/non-strict mode settings)
             */
            if(is_bool($value))
            {
                $value = (int)$value;
            }

            /**
             * Patch/currently not actively required, but
             * make shure we handle array-values in a useful way with MySQL
             */
            if(is_array($value))
            {
                $value = implode(',', $value);
            }

            $query->set($field, $value);
        }

        $this->andWhereByCallback($andWhereCallback, $query);
        $query->setMaxResults(1);

        $this->currentQuery = $query;

        return $this;
    }

    /**
     * @param array $updateArray
     * @param null $andWhereCallback
     * @param int $limit
     * @return \Mosaiq\MqTools\Domain\Orm\DoctrineOrmUtilities
     */
    public function updateQuery(array $updateArray, $andWhereCallback = null, int $limit = -1) : self
    {
        $query = $this->createQuery();
        $query->update($this->tableName);

        foreach($updateArray as $field => $value)
        {
            // Prevent modification of Uid!
            if($field === 'uid')
            {
                continue;
            }

            // harmonize entity-keys from camel-case to lower-case-underscored (f.e. model-entities)
            $field = GeneralUtility::camelCaseToLowerCaseUnderscored($field);

            $query->set($field, $value);
        }

        if($limit > -1)
        {
            $query->setMaxResults($limit);
        }

        $this->andWhereByCallback($andWhereCallback, $query);

        $this->currentQuery = $query;

        return $this;
    }

    /**
     * @param array $entity
     * @param string $uidFieldName
     * @return array|null
     */
    public function insertQuery(array $entity, string $uidFieldName = 'uid') :? array
    {
        try
        {
            // harmonize entity-keys from camel-case to lower-case-underscored (f.e. model-entities)
            $entityKeys = array_keys($entity);
            $entityKeys = array_map(function($key) {
                return GeneralUtility::camelCaseToLowerCaseUnderscored($key);
            }, $entityKeys);

            $entity = array_combine($entityKeys, $entity);

            $query = $this->createQuery();
            $query->insert($this->tableName)->values($entity);

            if($query->execute())
            {
                if (!empty($uidFieldName))
                {
                    $insertId = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tableName)->lastInsertId($this->tableName, $uidFieldName);

                    $entity[$uidFieldName] = $insertId;
                }

                return $entity;
            }

            return null;
        }
        catch (\Exception $e)
        {}

        return null;
    }

    /**
     * @param string|array $select
     * @param null $andWhereCallback
     * @param int|null $uid
     * @return \Mosaiq\MqTools\Domain\Orm\DoctrineOrmUtilities
     */
    public function select($select = '*', $andWhereCallback = null, $uid = null) : self
    {
        $query = $this->createQuery();
        $query->from($this->tableName);

        if(empty($select))
        {
            $select = ['*'];
        }
        else if(is_string($select))
        {
            $select = GeneralUtility::trimExplode(',', $select);
        }

        call_user_func_array([$query, 'select'], $select);

        $this->andWhereUid($uid);
        $this->andWhereByCallback($andWhereCallback, $query);

        $this->currentQuery = $query;

        return $this;
    }

    /**
     * @param string $countBy
     * @param null $andWhereCallback
     * @param null $uid
     * @return \Mosaiq\MqTools\Domain\Orm\DoctrineOrmUtilities
     */
    public function countQuery(string $countBy = '*', $andWhereCallback = null, $uid = null) : self
    {
        $query = $this->createQuery();
        $query
            ->count($countBy)
            ->from($this->tableName)
        ;

        $this->andWhereUid($uid);
        $this->andWhereByCallback($andWhereCallback, $query);

        return $this;
    }

    /**
     * Use with attention. This will flush the whole table-data!
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function flush() : bool
    {
        if(empty($this->tableName))
        {
            return null;
        }

        /** @var \Doctrine\DBAL\Connection $tableConnection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class);
        $tableConnection = $connection->getConnectionForTable($this->tableName);

        $tableConnection->beginTransaction();

        try
        {
            $tableConnection->query('SET FOREIGN_KEY_CHECKS=0');
            $tableConnection->query(sprintf('DELETE FROM %s', $this->tableName));
            $tableConnection->query('SET FOREIGN_KEY_CHECKS=1');

            $tableConnection->commit();
            return true;
        }
        catch (\Exception $e)
        {
            $tableConnection->rollBack();

            throw $e;
        }
    }
#endregion

#region DataMapper
    /**
     * @param string $repositoryOrModelClassName
     * @return DomainObjectCollection|null
     */
    public function mapAsModelCollection(string $repositoryOrModelClassName) : ?DomainObjectCollection
    {
        $rows = [];

        if($this->currentQuery instanceof QueryBuilder)
        {
            $rows = $this->currentQuery->execute()->fetchAll();
        }
        else if($this->currentQuery instanceof \Doctrine\DBAL\Driver\Statement)
        {
            if($this->currentQuery->execute())
            {
                $rows = $this->currentQuery->fetchAll();
            }
        }

        if(!count($rows))
        {
            return null;
        }

        $dataMapper = StaticObjectManager::get(DataMapper::class);
        $mappedRows = $dataMapper->map(
            ClassNamingUtility::translateRepositoryNameToModelName($repositoryOrModelClassName),
            $rows
        );

        if(count($rows))
        {
            return new DomainObjectCollection($mappedRows);
        }

        return null;
    }

    public function mapAsObjectCollection()
    {
        if($this->currentQuery instanceof QueryBuilder)
        {
            $rows = $this->currentQuery->execute()->fetchAll();
        }
        else if($this->currentQuery instanceof \Doctrine\DBAL\Driver\Statement)
        {
            if($this->currentQuery->execute())
            {
                $rows = $this->currentQuery->fetchAll();
            }
        }

        if(!count($rows))
        {
            return null;
        }

        return new ObjectCollection($rows);
    }
#endregion
}
