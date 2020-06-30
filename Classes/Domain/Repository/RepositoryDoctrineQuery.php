namespace Foo\FooBar\Domain\Repository;


class MyCustomRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * @var \TYPO3\CMS\Core\Database\Query\QueryBuilder
     * @inject
     */
    protected $queryBuilder;

    /**
     * @var string
     */
    protected $tableName;


    public function initializeObject()
    {
        parent::initializeObject();

        $dataMapper = $this->objectManager->get(DataMapper::class);
        $connectionPool = $this->objectManager->get(ConnectionPool::class);

        $className = substr(self::class, 0, strrpos(self::class, 'Repository'));
        $className = str_replace('\\Repository\\', '\\Model\\', $className);
        $tableName = $dataMapper->getDataMap($className)->getTableName();
        
        $this->tableName = $tableName;
        $this->queryBuilder = $connectionPool->getQueryBuilderForTable($tableName);
    }
    
    public function getSomething()
    {
      $q = $queryBuilder
         ->select('*')
         ->from($this->tableName)
         ->where($queryBuilder->expr()->eq('deleted', 0))
      ;
      
      return $q->execute()->fetchAll();
    }
}
