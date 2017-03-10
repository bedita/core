<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2016 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace BEdita\Core\Model\Table;

use Cake\Database\Schema\TableSchema;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * Objects Model
 *
 * @property \Cake\ORM\Association\BelongsTo $ObjectTypes
 * @property \Cake\ORM\Association\BelongsTo $CreatedByUser
 * @property \Cake\ORM\Association\BelongsTo $ModifiedByUser
 *
 * @since 4.0.0
 */
class ObjectsTable extends Table
{

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('objects');
        $this->setEntityClass('BEdita\Core\Model\Entity\ObjectEntity');
        $this->setPrimaryKey('id');
        $this->setDisplayField('title');

        $this->addBehavior('Timestamp');

        $this->addBehavior('BEdita/Core.DataCleanup');

        $this->addBehavior('BEdita/Core.UserModified');

        $this->addBehavior('BEdita/Core.Relations');

        $this->hasMany('DateRanges', [
            'foreignKey' => 'object_id',
            'className' => 'BEdita/Core.DateRanges',
        ]);

        $this->belongsTo('ObjectTypes', [
            'foreignKey' => 'object_type_id',
            'joinType' => 'INNER',
            'className' => 'BEdita/Core.ObjectTypes'
        ]);

        $this->belongsTo('CreatedByUser', [
            'foreignKey' => 'created_by',
            'className' => 'BEdita/Core.Users'
        ]);

        $this->belongsTo('ModifiedByUser', [
            'foreignKey' => 'modified_by',
            'className' => 'BEdita/Core.Users'
        ]);

        $this->addBehavior('BEdita/Core.UniqueName');
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->naturalNumber('id')
            ->allowEmpty('id', 'create')

            ->notEmpty('status')

            ->requirePresence('uname', 'create')
            ->notEmpty('uname')
            ->add('uname', 'unique', ['rule' => 'validateUnique', 'provider' => 'table'])

            ->boolean('locked')
            ->notEmpty('locked')

            ->boolean('deleted')
            ->notEmpty('deleted')

            ->dateTime('published')
            ->allowEmpty('published')

            ->allowEmpty('title')

            ->allowEmpty('description')

            ->allowEmpty('body')

            ->allowEmpty('extra')

            ->allowEmpty('lang')

            ->dateTime('publish_start')
            ->allowEmpty('publish_start')

            ->dateTime('publish_end')
            ->allowEmpty('publish_end');

        return $validator;
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->isUnique(['uname']));
        $rules->add($rules->existsIn(['object_type_id'], 'ObjectTypes'));
        $rules->add($rules->existsIn(['created_by'], 'CreatedByUser'));
        $rules->add($rules->existsIn(['modified_by'], 'ModifiedByUser'));

        return $rules;
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    protected function _initializeSchema(TableSchema $schema)
    {
        $schema->columnType('extra', 'json');

        return $schema;
    }

    /**
     * Find by object type.
     *
     * You can pass a list of allowed object types to this finder:
     *
     * ```
     * $table->find('type', [1, 'document', 'profiles', 1004]);
     * ```
     *
     * @param \Cake\ORM\Query $query Query object instance.
     * @param array $options Array of acceptable object types.
     * @return \Cake\ORM\Query
     */
    public function findType(Query $query, array $options)
    {
        $ObjectTypes = TableRegistry::get('ObjectTypes');
        foreach ($options as &$type) {
            $type = $ObjectTypes->get($type)->id;
        }
        unset($type);

        $query->where([$this->aliasField('object_type_id') . ' IN' => $options]);

        return $query;
    }

    /**
     * Find by date range using `DateRanges` table findDate filter
     *
     * @param \Cake\ORM\Query $query Query object instance.
     * @param array $options Array of acceptable date range conditions.
     * @return \Cake\ORM\Query
     */
    public function findDateRanges(Query $query, array $options)
    {
        return TableRegistry::get('DateRanges')->findDateRanges($query, $options, $this->alias());
    }
}
