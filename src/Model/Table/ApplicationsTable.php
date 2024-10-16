<?php
declare(strict_types=1);

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

use BEdita\Core\Exception\ImmutableResourceException;
use BEdita\Core\Search\SimpleSearchTrait;
use BEdita\Core\State\CurrentApplication;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Utility\Security;
use Cake\Utility\Text;
use Cake\Validation\Validator;

/**
 * Applications Model
 *
 * @method \BEdita\Core\Model\Entity\Application get($primaryKey, $options = [])
 * @method \BEdita\Core\Model\Entity\Application newEntity($data = null, array $options = [])
 * @method \BEdita\Core\Model\Entity\Application[] newEntities(array $data, array $options = [])
 * @method \BEdita\Core\Model\Entity\Application|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \BEdita\Core\Model\Entity\Application patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \BEdita\Core\Model\Entity\Application[] patchEntities($entities, array $data, array $options = [])
 * @method \BEdita\Core\Model\Entity\Application findOrCreate($search, callable $callback = null, $options = [])
 * @method \Cake\ORM\Query queryCache(\Cake\ORM\Query $query, string $key)
 * @property \Cake\ORM\Association\HasMany $EndpointPermissions
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @since 4.0.0
 */
class ApplicationsTable extends Table
{
    use SimpleSearchTrait;

    /**
     * Default application id
     *
     * @var int
     */
    public const DEFAULT_APPLICATION = 1;

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setDisplayField('name');
        $this->addBehavior('Timestamp');
        $this->addBehavior('BEdita/Core.Searchable', ['scopes' => (array)$this->getTable()]);
        $this->addBehavior('BEdita/Core.QueryCache');
        $this->addBehavior('BEdita/Core.ResourceName');

        $this->hasMany('EndpointPermissions', [
            'dependent' => true,
        ]);

        $this->setupSimpleSearch(['fields' => ['name', 'description']]);
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create')

            ->notEmptyString('api_key')
            ->add('api_key', 'unique', ['rule' => 'validateUnique', 'provider' => 'table'])

            ->requirePresence('name', 'create')
            ->notEmptyString('name')
            ->add('name', 'unique', ['rule' => 'validateUnique', 'provider' => 'table'])

            ->allowEmptyString('description')

            ->boolean('enabled')
            ->notEmptyString('enabled');

        return $validator;
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['name']));
        $rules->add($rules->isUnique(['api_key']));

        return $rules;
    }

    /**
     * Generate the api key on application creation.
     *
     * If applications is DEFAULT_APPLICATION or current invoking application and `enabled` is `false`
     * raise an ImmutableResourceException
     *
     * @param \Cake\Event\EventInterface $event The event dispatched
     * @param \Cake\Datasource\EntityInterface $entity The entity to save
     * @return void
     * @throws \BEdita\Core\Exception\ImmutableResourceException if entity is not disableable
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity)
    {
        if (
            !$entity->isNew() && $entity->get('enabled') == false &&
            in_array($entity->id, [static::DEFAULT_APPLICATION, CurrentApplication::getApplicationId()])
        ) {
            throw new ImmutableResourceException(__d('bedita', 'Could not disable "Application" {0}', $entity->id));
        }

        if ($entity->isNew() && !$entity->has('api_key')) {
            $entity->set('api_key', static::generateApiKey());
        }
    }

    /**
     * Generate a unique api key
     *
     * @return string
     */
    public static function generateApiKey()
    {
        return Security::hash(Text::uuid(), 'sha1');
    }

    /**
     * Find an active application by its API key.
     *
     * @param \Cake\ORM\Query $query Query object instance.
     * @param array $options Options array. It requires an `apiKey` key.
     * @return \Cake\ORM\Query
     */
    protected function findApiKey(Query $query, array $options): Query
    {
        if (empty($options['apiKey']) || !is_string($options['apiKey'])) {
            throw new \BadMethodCallException('Required option "apiKey" must be a not empty string');
        }

        $query = $query->where([
            $this->aliasField('api_key') => $options['apiKey'],
            $this->aliasField('enabled') => true,
        ]);

        return $this->queryCache($query, sprintf('app_%s', $options['apiKey']));
    }

    /**
     * Find an active application by client_id and client_secret.
     *
     * @param \Cake\ORM\Query $query Query object instance.
     * @param array $options Options array. It requires an `apiKey` key.
     * @return \Cake\ORM\Query
     */
    protected function findCredentials(Query $query, array $options): Query
    {
        if (empty($options['client_id'])) {
            throw new \BadMethodCallException('Required option "client_id" must be a not empty string');
        }

        return $query->where(function (QueryExpression $exp) use ($options) {
            $secret = Hash::get($options, 'client_secret');
            if ($secret !== null) {
                $exp = $exp->eq($this->aliasField('client_secret'), $secret);
            } else {
                $exp = $exp->isNull($this->aliasField('client_secret'));
            }
            $exp = $exp->eq($this->aliasField('enabled'), true);

            return $exp->eq($this->aliasField('api_key'), $options['client_id']);
        });
    }

    /**
     * Finder to find all enabled applications
     *
     * @param \Cake\ORM\Query $query Query object.
     * @return \Cake\ORM\Query
     */
    protected function findEnabled(Query $query): Query
    {
        return $query->where([
            $this->aliasField('enabled') => true,
        ]);
    }

    /**
     * Before delete checks: if applications is DEFAULT_APPLICATION or current raise a ImmutableResourceException
     *
     * @param \Cake\Event\EventInterface $event The beforeSave event that was fired
     * @param \Cake\Datasource\EntityInterface $entity the entity that is going to be saved
     * @return void
     * @throws \BEdita\Core\Exception\ImmutableResourceException if entity is not deletable
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity)
    {
        if (in_array($entity->id, [static::DEFAULT_APPLICATION, CurrentApplication::getApplicationId()])) {
            throw new ImmutableResourceException(__d('bedita', 'Could not delete "Application" {0}', $entity->id));
        }
    }
}
