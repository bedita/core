<?php
namespace BEdita\Core\Model\Table;

use BEdita\Core\Model\Table\ObjectsBaseTable as Table;
use BEdita\Core\Model\Validation\MediaValidator;
use Cake\Database\Schema\TableSchemaInterface;

/**
 * Media Model
 *
 * @method \BEdita\Core\Model\Entity\Media get($primaryKey, $options = [])
 * @method \BEdita\Core\Model\Entity\Media newEntity($data = null, array $options = [])
 * @method \BEdita\Core\Model\Entity\Media[] newEntities(array $data, array $options = [])
 * @method \BEdita\Core\Model\Entity\Media|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \BEdita\Core\Model\Entity\Media patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \BEdita\Core\Model\Entity\Media[] patchEntities($entities, array $data, array $options = [])
 * @method \BEdita\Core\Model\Entity\Media findOrCreate($search, callable $callback = null, $options = [])
 * @since 4.0.0
 */
class MediaTable extends Table
{
    /**
     * @inheritDoc
     */
    protected $_validatorClass = MediaValidator::class;

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('media');
        $this->setPrimaryKey('id');
        $this->setDisplayField('name');

        $this->extensionOf('Objects');

        $this->getBehavior('UniqueName')->setConfig([
            'sourceField' => 'title',
            'prefix' => 'media-',
        ]);

        $this->hasMany('Streams', [
            'foreignKey' => 'object_id',
            'className' => 'BEdita/Core.Streams',
        ]);

        $this->setupSimpleSearch([
            'fields' => [
                'title',
                'description',
                'body',
                'provider',
                'name',
            ],
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    protected function _initializeSchema(TableSchemaInterface $schema): TableSchemaInterface
    {
        $schema->setColumnType('provider_extra', 'json');

        return $schema;
    }
}
