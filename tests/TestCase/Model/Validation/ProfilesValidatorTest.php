<?php
declare(strict_types=1);

/**
 * BEdita, API-first content management framework
 * Copyright 2017 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace BEdita\Core\Test\TestCase\Model\Validation;

use BEdita\Core\Model\Validation\ProfilesValidator;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;

/**
 * @covers \BEdita\Core\Model\Validation\ProfilesValidator
 */
class ProfilesValidatorTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'plugin.BEdita/Core.ObjectTypes',
        'plugin.BEdita/Core.Relations',
        'plugin.BEdita/Core.RelationTypes',
        'plugin.BEdita/Core.Objects',
        'plugin.BEdita/Core.Profiles',
    ];

    /**
     * Data provider for `testValidation` test case.
     *
     * @return array
     */
    public function validationProvider()
    {
        return [
            'empty' => [
                [],
                [],
            ],
            'missing fields on update' => [
                [
                    'id._required',
                ],
                [],
                false,
            ],
            'empty fields' => [
                [
                    'status._empty',
                ],
                [
                    'status' => '',
                ],
            ],
            'invalid types' => [
                [
                    'id.naturalNumber',
                    'status.inList',
                    'uname.ascii',
                    'locked.boolean',
                    'deleted.boolean',
                    'published.dateTime',
                    'publish_start.dateTime',
                    'publish_end.dateTime',
                    'email.email',
                    'birthdate.date',
                    'deathdate.date',
                    'company.boolean',
                    'website.url',
                ],
                [
                    'id' => -pi(),
                    'status' => 'neither on, nor off... maybe draft',
                    'uname' => 'àèìòù',
                    'locked' => 'yes',
                    'deleted' => 'maybe',
                    'published' => 'tomorrow',
                    'publish_start' => 'someday',
                    'publish_end' => 'somewhen',
                    'email' => 'http://not.an.email.example.org/',
                    'birthdate' => 'a gloomy day',
                    'deathdate' => 'a glorious day',
                    'company' => 'good company',
                    'website' => 'not.an.url@example.org',
                ],
            ],
            'URL without protocol' => [
                [
                    'website.url',
                ],
                [
                    'website' => 'www.example.com/without/protocol.txt?shouldBeValid=no',
                ],
            ],
            'invalid name' => [
                [
                    'name.validName',
                ],
                [
                    'name' => 'http://someurl',
                ],
            ],
            'invalid surname' => [
                [
                    'surname.validName',
                ],
                [
                    'surname' => 'gustavo.com',
                ],
            ],
            'valid surname' => [
                [
                ],
                [
                    'surname' => 'Support Jr.',
                ],
            ],
        ];
    }

    /**
     * Test validation.
     *
     * @param array $expected Expected validation errors.
     * @param array $data Data being validated.
     * @param bool $newRecord Is this a new record?
     * @return void
     * @dataProvider validationProvider()
     */
    public function testValidation(array $expected, array $data, $newRecord = true)
    {
        $validator = new ProfilesValidator();

        $errors = $validator->validate($data, $newRecord);
        $errors = Hash::flatten($errors);

        static::assertEquals($expected, array_keys($errors));
    }
}
